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

// Independent re-statement of the expected escaping (JSON + HTML/line-separator safe).
const escapedLiteral = value =>
    JSON.stringify(value)
        .replace(/</g, '\\u003C')
        .replace(/>/g, '\\u003E')
        .replace(/\u2028/g, '\\u2028')
        .replace(/\u2029/g, '\\u2029');

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
        assert.doesNotMatch(output, /\brequire\(/);
        assert.ok(
            output.includes(
                `load: () => import(/* webpackChunkName: "module-external-script-module" */ ${escapedLiteral(ref)}) }`,
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
        assert.equal(sandbox.injected, undefined);
    }
});

test('emits one lazy chunk per package with a sanitized chunk name', () => {
    const output = compile({
        'AmazingCat_Redmine*/Integration': { type: 'package', ref: '@amazingcat/cattr-redmine' },
        Other_Module: { type: 'package', ref: 'other-module' },
    });

    assert.equal(
        output,
        [
            'export default [',
            '    { name: "AmazingCat_Redmine*/Integration", load: () => import(/* webpackChunkName: "module-amazingcat_redmine-integration" */ "@amazingcat/cattr-redmine") },',
            '    { name: "Other_Module", load: () => import(/* webpackChunkName: "module-other_module" */ "other-module") },',
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
