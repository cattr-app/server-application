const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const moduleDir = path.join(__dirname, '../../resources/frontend/core/modules/TimeIntervals');

function loadRecentTasks() {
    const script = fs.readFileSync(path.join(moduleDir, 'recentTasks.js'), 'utf8').replace(/^export /gm, '');
    const sandbox = {};
    vm.runInNewContext(
        script +
            '\nthis.api = { RECENT_TASKS_LIMIT, storageKey, normalizeTaskIds, readRecentTaskIds, rememberRecentTask };',
        sandbox,
    );
    return sandbox.api;
}

const recent = loadRecentTasks();

function createStorage(initial = {}) {
    const data = { ...initial };
    return {
        data,
        getItem: key => (Object.hasOwn(data, key) ? data[key] : null),
        setItem: (key, value) => {
            data[key] = String(value);
        },
    };
}

const toArray = value => Array.from(value);

test('a used task moves to the top without duplicates', () => {
    const storage = createStorage();
    recent.rememberRecentTask(1, 10, storage);
    recent.rememberRecentTask(1, 20, storage);
    recent.rememberRecentTask(1, 10, storage);
    assert.deepEqual(toArray(recent.readRecentTaskIds(1, storage)), [10, 20]);
});

test('history is capped at 10 tasks', () => {
    const storage = createStorage();
    for (let id = 1; id <= 12; id++) recent.rememberRecentTask(1, id, storage);
    assert.equal(recent.RECENT_TASKS_LIMIT, 10);
    assert.deepEqual(toArray(recent.readRecentTaskIds(1, storage)), [12, 11, 10, 9, 8, 7, 6, 5, 4, 3]);
});

test('history is scoped to the signed-in account', () => {
    const storage = createStorage();
    recent.rememberRecentTask(1, 10, storage);
    recent.rememberRecentTask(2, 20, storage);
    assert.deepEqual(toArray(recent.readRecentTaskIds(1, storage)), [10]);
    assert.deepEqual(toArray(recent.readRecentTaskIds(2, storage)), [20]);
    assert.deepEqual(toArray(recent.readRecentTaskIds(3, storage)), []);
    assert.deepEqual(toArray(recent.readRecentTaskIds(undefined, storage)), []);
    assert.deepEqual(toArray(recent.rememberRecentTask(null, 10, storage)), []);
    assert.equal(recent.storageKey(null), null);
});

test('malformed stored data is ignored', () => {
    const key = recent.storageKey(1);
    for (const value of ['not json', '{"id":1}', 'null', '"5"']) {
        assert.deepEqual(toArray(recent.readRecentTaskIds(1, createStorage({ [key]: value }))), []);
    }
    const mixed = createStorage({ [key]: JSON.stringify([3, '4', -1, 0, 1.5, 'x', null, {}, 3, 1e20]) });
    assert.deepEqual(toArray(recent.readRecentTaskIds(1, mixed)), [3, 4]);
    recent.rememberRecentTask(1, 'abc', mixed);
    assert.deepEqual(toArray(recent.readRecentTaskIds(1, mixed)), [3, 4]);
});

test('storage failures do not break reading or saving', () => {
    const broken = {
        getItem() {
            throw new Error('denied');
        },
        setItem() {
            throw new Error('quota');
        },
    };
    assert.deepEqual(toArray(recent.readRecentTaskIds(1, broken)), []);
    assert.deepEqual(toArray(recent.rememberRecentTask(1, 5, broken)), [5]);
    assert.deepEqual(toArray(recent.readRecentTaskIds(1, null)), []);
    assert.deepEqual(toArray(recent.rememberRecentTask(1, 5, null)), [5]);
});

function loadManualService({ save, userId = 1 }) {
    const source = fs.readFileSync(path.join(moduleDir, 'module.init.js'), 'utf8');
    const match = source.match(/class ManualTimeIntervalService[\s\S]*?\n}\n/);
    assert.ok(match, 'ManualTimeIntervalService not found');
    const remembered = [];
    const sandbox = {
        TimeIntervalService: class {
            save(data) {
                return save(data);
            }
        },
        toIntervalPayload: data => ({ ...data, payload: true }),
        rememberRecentTask: (accountId, taskId) => remembered.push([accountId, taskId]),
        rootStore: { getters: { 'user/user': { id: userId } } },
    };
    vm.runInNewContext(match[0] + '\nthis.Service = ManualTimeIntervalService;', sandbox);
    return { service: new sandbox.Service(), remembered };
}

test('history is updated only after time-intervals/create succeeds', async () => {
    const { service, remembered } = loadManualService({ save: async data => ({ data: { data } }) });
    const response = await service.save({ task_id: 7, user_id: 3 });
    assert.equal(response.data.data.payload, true);
    assert.deepEqual(remembered, [[1, 7]]);
});

test('a rejected save leaves history unchanged', async () => {
    const { service, remembered } = loadManualService({
        save: async () => {
            throw { response: { status: 422 } };
        },
    });
    await assert.rejects(service.save({ task_id: 7 }));
    assert.deepEqual(remembered, []);
});
