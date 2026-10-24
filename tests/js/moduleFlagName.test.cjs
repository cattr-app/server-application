const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const fixtures = require('../fixtures/module-flag-names.json');

// The util is an ES module for the webpack build; strip the export keywords and
// evaluate it in an isolated context so the test exercises the production source.
function loadModuleFlagName() {
    const source = fs.readFileSync(
        path.join(__dirname, '../../resources/frontend/core/utils/moduleFlagName.js'),
        'utf8',
    );
    const script = source.replace(/^export default .*$/m, '').replace(/^export /gm, '');
    const sandbox = {};
    vm.runInNewContext(`${script}\nthis.moduleFlagName = moduleFlagName;`, sandbox);
    return sandbox.moduleFlagName;
}

const moduleFlagName = loadModuleFlagName();

test('fixtures are not empty', () => {
    assert.ok(Object.keys(fixtures).length > 0);
});

for (const [identifier, expected] of Object.entries(fixtures)) {
    test(`moduleFlagName(${JSON.stringify(identifier)}) === ${JSON.stringify(expected)}`, () => {
        assert.equal(moduleFlagName(identifier), expected);
    });
}
