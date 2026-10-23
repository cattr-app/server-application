const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const compilerSource = fs.readFileSync(
    path.join(__dirname, '../../resources/frontend/compiler/index.js'),
    'utf8',
);
const frontendDir = '/test/frontend';

function compile(moduleList) {
    let generated;
    const fakeFs = {
        existsSync: file =>
            file === path.join(frontendDir, 'generated') ||
            file === path.join(frontendDir, 'etc/modules.config.json'),
        writeFileSync: (_file, contents) => {
            generated = contents;
        },
    };
    const fakeRequire = id => {
        if (id === 'fs') return fakeFs;
        if (id === 'path') return path;
        if (id === 'lodash/isObject') return value => value !== null && typeof value === 'object';
        if (id === 'lodash/merge') return Object.assign;
        if (id === path.join(frontendDir, 'etc/modules.config.json')) return moduleList;
        throw new Error('Unexpected require: ' + id);
    };

    const module = { exports: {} };
    vm.runInNewContext(compilerSource, {
        module,
        require: fakeRequire,
        __dirname: path.join(frontendDir, 'compiler'),
        console: { log() {}, error() {} },
        process: { env: { NODE_ENV: 'test' } },
    });
    module.exports();
    return generated;
}

test('emits valid, faithfully escaped JavaScript package references', () => {
    for (const ref of [
        '@amazingcat/cattr-redmine-integration',
        '@scope/subpath/module',
        'a"b\\c\nd',
        '</script>\u2028\u2029',
        'pkg"); globalThis.injected = true; //',
    ]) {
        const name = 'External"</script>\u2028Module';
        const output = compile({ [name]: { type: 'package', ref } });
        assert.doesNotMatch(output, /<\/script>/);
        assert.doesNotMatch(output, /[\u2028\u2029]/);
        assert.match(output, /\\u003C/);
        const sandbox = { module: { exports: null }, require: requested => requested };
        vm.runInNewContext(output.replace(/^export default \[/, 'module.exports = ['), sandbox);

        assert.equal(sandbox.module.exports.length, 1);
        assert.equal(sandbox.module.exports[0].name, name);
        assert.equal(sandbox.module.exports[0].load(), ref);
        assert.equal(sandbox.injected, undefined);
    }
});

test('rejects absent, non-string, and empty package references', () => {
    for (const ref of [undefined, null, 123, {}, [], '', '  \t ']) {
        assert.throws(
            () => compile({ InvalidModule: { type: 'package', ref } }),
            /Invalid package reference for module InvalidModule/,
        );
    }
});

test('ignores unrelated module types', () => {
    const output = compile({ LocalModule: { type: 'local' } });
    assert.equal(output.trim(), 'export default [\n];');
});
