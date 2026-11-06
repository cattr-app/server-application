const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

// The util is an ES module for the webpack build; strip the export keywords and
// evaluate it in an isolated context so the test exercises the production source.
function loadReverbConfig() {
    const source = fs.readFileSync(path.join(__dirname, '../../resources/frontend/core/utils/reverbConfig.js'), 'utf8');
    const script = source.replace(/^export default .*$/m, '').replace(/^export /gm, '');
    const sandbox = {};
    vm.runInNewContext(`${script}\nthis.reverbConfig = reverbConfig;`, sandbox);
    return sandbox.reverbConfig;
}

const reverbConfig = loadReverbConfig();

test('an https page on the default port uses TLS on 443', () => {
    const config = reverbConfig({ protocol: 'https:', hostname: 'demo.cattr.app', port: '' });

    assert.equal(config.forceTLS, true);
    assert.equal(config.wsHost, 'demo.cattr.app');
    assert.equal(config.wsPort, 443);
    assert.equal(config.wssPort, 443);
    assert.equal(config.wsPath, '');
});

test('an http page on the default port uses 80 without TLS', () => {
    const config = reverbConfig({ protocol: 'http:', hostname: 'cattr.local', port: '' });

    assert.equal(config.forceTLS, false);
    assert.equal(config.wsPort, 80);
});

test('an explicit page port is kept', () => {
    const config = reverbConfig({ protocol: 'http:', hostname: 'localhost', port: '8080' });

    assert.equal(config.wsPort, '8080');
    assert.equal(config.wssPort, '8080');
});

test('build-time overrides win over the page location', () => {
    const config = reverbConfig(
        { protocol: 'https:', hostname: 'demo.cattr.app', port: '' },
        { host: 'ws.cattr.app', path: '/ws', port: '6001' },
    );

    assert.equal(config.wsHost, 'ws.cattr.app');
    assert.equal(config.wsPath, '/ws');
    assert.equal(config.wsPort, '6001');
    assert.equal(config.forceTLS, true);
});

test('undefined overrides fall back to the page location', () => {
    const config = reverbConfig(
        { protocol: 'https:', hostname: 'demo.cattr.app', port: '' },
        { host: undefined, path: undefined, port: undefined },
    );

    assert.equal(config.wsHost, 'demo.cattr.app');
    assert.equal(config.wsPath, '');
    assert.equal(config.wsPort, 443);
});
