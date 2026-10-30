const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../resources/frontend/core/utils/storageCleanup.js'), 'utf8');

function load() {
    // The util is an ES module for the webpack build: drop `export` and run it in a sandbox.
    const sandbox = { Promise };
    vm.runInNewContext(
        `${source.replace(/^export /gm, '')}
this.utils = { cleanupStatus, isCleanupActive, finishedCleanupStatus, createStoragePoller };`,
        sandbox,
    );
    return sandbox.utils;
}

const { cleanupStatus, isCleanupActive, finishedCleanupStatus, createStoragePoller } = load();

function fakeTimers() {
    const pending = new Map();
    let next = 1;

    return {
        pending,
        setTimeout(fn, delay) {
            const id = next++;
            pending.set(id, { fn, delay });
            return id;
        },
        clearTimeout(id) {
            pending.delete(id);
        },
        // Runs the only pending timer and returns its delay
        fire() {
            assert.equal(pending.size, 1, 'exactly one timer is pending');
            const [[id, { fn, delay }]] = pending;
            pending.delete(id);
            fn();
            return delay;
        },
    };
}

function deferred() {
    let resolve;
    let reject;
    const promise = new Promise((res, rej) => {
        resolve = res;
        reject = rej;
    });
    return { promise, resolve, reject };
}

const flush = () => new Promise(resolve => setImmediate(resolve));

const storage = (status, extra = {}) => ({
    screenshots_available: 10,
    thinning: { now: status === 'running', last: null, status, ...extra },
});

function setup(responses, options = {}) {
    const timers = fakeTimers();
    const updates = [];
    const errors = [];
    const queue = [...responses];
    const poller = createStoragePoller({
        fetch: () => {
            const next = queue.shift();
            return next instanceof Error
                ? Promise.reject(next)
                : next && next.promise
                  ? next.promise
                  : Promise.resolve(next);
        },
        onUpdate: value => updates.push(value),
        onError: error => errors.push(error),
        activeInterval: 10,
        idleInterval: 60,
        timers,
        ...options,
    });

    return { poller, timers, updates, errors };
}

test('status falls back to thinning.now for a server without thinning.status', () => {
    assert.equal(cleanupStatus({ thinning: { now: true, last: null } }), 'running');
    assert.equal(cleanupStatus({ thinning: { now: null, last: null } }), 'idle');
    assert.equal(cleanupStatus(null), 'idle');
    assert.equal(isCleanupActive(storage('queued')), true);
    assert.equal(isCleanupActive(storage('failed')), false);
});

test('a finished cleanup is reported only after it was seen active', () => {
    assert.equal(finishedCleanupStatus(storage('queued'), storage('running')), null);
    assert.equal(finishedCleanupStatus(storage('running'), storage('completed')), 'completed');
    assert.equal(finishedCleanupStatus(storage('queued'), storage('skipped')), 'skipped');
    assert.equal(finishedCleanupStatus(storage('running'), storage('failed')), 'failed');
    // Status left from an earlier run when the page is opened
    assert.equal(finishedCleanupStatus(null, storage('completed')), null);
    assert.equal(finishedCleanupStatus(storage('completed'), storage('completed')), null);
});

test('queued -> running -> completed is polled at the active interval, then idle', async () => {
    const { poller, timers, updates } = setup([
        storage('queued'),
        storage('running'),
        storage('completed', { last: '2026-10-30T12:00:00+00:00' }),
    ]);

    await poller.start();
    assert.deepEqual(updates.map(cleanupStatus), ['queued']);

    assert.equal(timers.fire(), 10);
    await flush();
    assert.deepEqual(updates.map(cleanupStatus), ['queued', 'running']);

    assert.equal(timers.fire(), 10);
    await flush();
    assert.deepEqual(updates.map(cleanupStatus), ['queued', 'running', 'completed']);
    assert.equal(updates[2].thinning.last, '2026-10-30T12:00:00+00:00');

    // Nothing in progress: slow refresh only
    assert.equal([...timers.pending.values()][0].delay, 60);
});

test('a skipped cleanup leaves the busy state', async () => {
    const { poller, timers, updates } = setup([storage('queued'), storage('skipped')]);

    await poller.start();
    timers.fire();
    await flush();

    assert.equal(finishedCleanupStatus(updates[0], updates[1]), 'skipped');
    assert.equal([...timers.pending.values()][0].delay, 60);
});

test('a failed refresh is reported and retried at the active interval', async () => {
    const { poller, timers, updates, errors } = setup([
        storage('idle'),
        new Error('Network Error'),
        storage('running'),
    ]);

    await poller.start();
    assert.equal(timers.fire(), 60);
    await flush();
    assert.equal(errors.length, 1);

    assert.equal(timers.fire(), 10);
    await flush();
    assert.deepEqual(updates.map(cleanupStatus), ['idle', 'running']);
});

test('setActive switches to the active interval after a cleanup request', async () => {
    const { poller, timers } = setup([storage('idle')]);

    await poller.start();
    assert.equal([...timers.pending.values()][0].delay, 60);

    poller.setActive(true);
    assert.equal(timers.pending.size, 1);
    assert.equal([...timers.pending.values()][0].delay, 10);
});

test('a stale response does not overwrite a newer one', async () => {
    const slow = deferred();
    const { poller, timers, updates } = setup([slow, storage('running')]);

    poller.start();
    await poller.refresh();
    assert.deepEqual(updates.map(cleanupStatus), ['running']);

    slow.resolve(storage('queued'));
    await flush();
    assert.deepEqual(updates.map(cleanupStatus), ['running']);
    assert.equal(timers.pending.size, 1);
});

test('nothing is applied or scheduled after stop', async () => {
    const pending = deferred();
    const { poller, timers, updates, errors } = setup([storage('running'), pending]);

    await poller.start();
    timers.fire();
    poller.stop();

    pending.resolve(storage('completed'));
    await flush();

    assert.deepEqual(updates.map(cleanupStatus), ['running']);
    assert.equal(errors.length, 0);
    assert.equal(timers.pending.size, 0);
});

test('a hidden page is not polled', async () => {
    let hidden = true;
    const { poller, timers, updates } = setup([storage('running'), storage('completed')], { isHidden: () => hidden });

    await poller.start();
    timers.fire();
    await flush();
    assert.equal(updates.length, 1);
    assert.equal(timers.pending.size, 1);

    hidden = false;
    timers.fire();
    await flush();
    assert.equal(updates.length, 2);
});
