const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

// The util is an ES module for the webpack build; strip the export keywords and
// evaluate it in an isolated context so the test exercises the production source.
function loadUtil() {
    const source = fs.readFileSync(
        path.join(__dirname, '../../resources/frontend/core/utils/screenshotUrl.js'),
        'utf8',
    );
    const script = source.replace(/^export /gm, '');
    const sandbox = { URL, Date, Number, Promise, Map, Infinity };
    vm.runInNewContext(
        `${script}\nthis.util = { EXPIRY_MARGIN_MS, urlExpiresAt, isUrlExpired, createScreenshotUrlCache };`,
        sandbox,
    );
    return sandbox.util;
}

const { EXPIRY_MARGIN_MS, urlExpiresAt, isUrlExpired, createScreenshotUrlCache } = loadUtil();

test('expiration of a Laravel signed route', () => {
    assert.equal(urlExpiresAt('/api/time-intervals/1/thumb?expires=1700000000&signature=abc'), 1700000000000);
});

test('expiration of an S3 presigned URL', () => {
    const url =
        'https://s3.example/bucket/screenshots/a.jpg?X-Amz-Algorithm=AWS4-HMAC-SHA256' +
        '&X-Amz-Date=20261025T120000Z&X-Amz-Expires=600&X-Amz-Signature=abc';
    assert.equal(urlExpiresAt(url), Date.UTC(2026, 9, 25, 12, 10, 0));
});

test('unknown expiration', () => {
    assert.equal(urlExpiresAt('/api/time-intervals/1/thumb'), null);
    assert.equal(isUrlExpired('/api/time-intervals/1/thumb'), true);
    assert.equal(isUrlExpired(null), true);
});

test('expired with a safety margin', () => {
    const url = '/x?expires=1000';
    assert.equal(isUrlExpired(url, 1000 * 1000 - EXPIRY_MARGIN_MS - 1), false);
    assert.equal(isUrlExpired(url, 1000 * 1000 - EXPIRY_MARGIN_MS), true);
});

test('cache shares one request per interval until the URLs expire', async () => {
    let now = 0;
    const calls = [];
    const cache = createScreenshotUrlCache(
        id => {
            calls.push(id);
            return Promise.resolve({ thumbnail_url: `t${calls.length}`, expires_in: 600 });
        },
        () => now,
    );

    const [a, b] = await Promise.all([cache.get(1), cache.get(1)]);
    assert.equal(a, b);
    assert.deepEqual(calls, [1]);

    now = 600 * 1000 - EXPIRY_MARGIN_MS - 1;
    await cache.get(1);
    assert.deepEqual(calls, [1]);

    now += 1;
    assert.equal((await cache.get(1)).thumbnail_url, 't2');
    assert.equal((await cache.get(1, { force: true })).thumbnail_url, 't3');
    assert.deepEqual(calls, [1, 1, 1]);
});

test('cache forgets failed requests', async () => {
    let fail = true;
    const cache = createScreenshotUrlCache(() =>
        fail ? Promise.reject(new Error('nope')) : Promise.resolve({ expires_in: 600, screenshot_url: 's' }),
    );

    await assert.rejects(cache.get(7));
    fail = false;
    assert.equal((await cache.get(7)).screenshot_url, 's');
});
