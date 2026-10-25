const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const compilerSource = fs.readFileSync(path.join(__dirname, '../../resources/frontend/compiler/index.js'), 'utf8');
const frontendDir = '/test/frontend';

function compile(moduleList) {
    let generated;
    const fakeFs = {
        existsSync: file =>
            file === path.join(frontendDir, 'generated') || file === path.join(frontendDir, 'etc/modules.config.json'),
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

test('emits plain literals for valid module names and package references', () => {
    for (const ref of [
        '@amazingcat/cattr-redmine-integration',
        '@scope/subpath/module',
        'plain-package',
        './generated/fake-module.js',
        '../sibling/module.init.js',
    ]) {
        const name = 'External_Module2';
        const output = compile({ [name]: { type: 'package', ref } });
        assert.doesNotMatch(output, /\brequire\(/);
        assert.ok(
            output.includes(
                `{ name: '${name}', load: () => import(/* webpackChunkName: "module-external_module2" */ '${ref}') }`,
            ),
            output,
        );

        // vm scripts cannot run a dynamic import() without a loader: route it to a stub.
        const sandbox = { module: { exports: null }, __import: requested => requested };
        vm.runInNewContext(
            output.replace(/^export default \[/, 'module.exports = [').replace(/=> import\(/g, '=> __import('),
            sandbox,
        );

        assert.equal(sandbox.module.exports.length, 1);
        assert.equal(sandbox.module.exports[0].name, name);
        assert.equal(sandbox.module.exports[0].load(), ref);
    }
});

test('rejects module names and package references outside the allowlist', () => {
    for (const name of [
        'External"</script>\u2028Module',
        'AmazingCat_Redmine*/Integration',
        'name with spaces',
        "x'; globalThis.injected = true; //",
    ]) {
        assert.throws(() => compile({ [name]: { type: 'package', ref: 'pkg' } }), /Invalid module name/);
    }
    for (const ref of [
        'a"b\\c\nd',
        '</script>\u2028\u2029',
        "pkg'); globalThis.injected = true; //",
        '../../etc/passwd',
        './dir/../escape.js',
        '/absolute/path.js',
        'pkg with space',
    ]) {
        assert.throws(
            () => compile({ ValidModule: { type: 'package', ref } }),
            /Invalid package reference for module ValidModule/,
        );
    }
});

test('emits one lazy chunk per package with a sanitized chunk name', () => {
    const output = compile({
        AmazingCat_RedmineIntegration: { type: 'package', ref: '@amazingcat/cattr-redmine' },
        Other_Module: { type: 'package', ref: 'other-module' },
    });

    assert.equal(
        output,
        [
            'export default [',
            "    { name: 'AmazingCat_RedmineIntegration', load: () => import(/* webpackChunkName: \"module-amazingcat_redmineintegration\" */ '@amazingcat/cattr-redmine') },",
            "    { name: 'Other_Module', load: () => import(/* webpackChunkName: \"module-other_module\" */ 'other-module') },",
            '];',
        ].join('\n'),
    );
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
