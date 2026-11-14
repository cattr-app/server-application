const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const file = path.join(__dirname, '../../resources/frontend/core/modules/OfflineSync/services/requestGuard.js');

function load() {
    const script = fs.readFileSync(file, 'utf8').replace(/^export /gm, '');
    const sandbox = {};
    vm.runInNewContext(script + '\nthis.api = { createRequestGuard };', sandbox);
    return sandbox.api;
}

const { createRequestGuard } = load();

test('the only started request is current', () => {
    const guard = createRequestGuard();
    assert.equal(guard.isCurrent(guard.begin()), true);
});

test('a newer request supersedes the previous one', () => {
    const guard = createRequestGuard();
    const first = guard.begin();
    const second = guard.begin();
    assert.equal(guard.isCurrent(first), false);
    assert.equal(guard.isCurrent(second), true);
});

test('no request is current once the owner is disposed', () => {
    const guard = createRequestGuard();
    const token = guard.begin();
    guard.dispose();
    assert.equal(guard.isCurrent(token), false);
    assert.equal(guard.isCurrent(guard.begin()), false);
});
