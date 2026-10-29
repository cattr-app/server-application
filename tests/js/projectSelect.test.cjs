const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const storageKey = 'amazingcat.local.storage.project_select';

function createSelector(storedSelection) {
    const values = new Map();
    if (storedSelection !== undefined) {
        values.set(storageKey, JSON.stringify(storedSelection));
    }

    const localStorage = {
        getItem: key => values.get(key) ?? null,
        setItem: (key, value) => values.set(key, String(value)),
    };

    const source = fs.readFileSync(
        path.join(__dirname, '../../resources/frontend/core/components/ProjectSelect.vue'),
        'utf8',
    );
    const openTag = '<script>';
    const closeTag = '</script>';
    const start = source.indexOf(openTag);
    const end = source.indexOf(closeTag, start + openTag.length);
    if (start === -1 || end === -1) {
        throw new Error('ProjectSelect.vue script block not found');
    }
    const script = source
        .slice(start + openTag.length, end)
        .replace(/^\s*import .*;\s*$/gm, '')
        .replace('export default {', 'const component = {');
    const sandbox = { localStorage, MultiSelect: class {}, ProjectService: class {}, JSON };
    vm.runInNewContext(`${script}\nthis.component = component;`, sandbox);

    const events = [];
    const instance = {
        ...sandbox.component.data.call({ value: null }),
        ...sandbox.component.methods,
        unfilteredWhenAllSelected: true,
        $emit: (event, value) => events.push({ event, value }),
        $nextTick: callback => callback(),
    };
    return { instance, methods: sandbox.component.methods, events };
}

function loadOptions(selector, ids) {
    selector.methods.onLoad.call(selector.instance, ids.map(id => ({ id })));
    return selector.events.find(event => event.event === 'change').value;
}

test('initial default includes historical intervals even without current projects', () => {
    const selector = createSelector();
    assert.equal(loadOptions(selector, []), null);
    assert.equal(selector.events.at(-1).event, 'loaded');
});

test('stored empty selection stays empty when there are no current projects', () => {
    const selector = createSelector([]);
    assert.deepEqual(Array.from(loadOptions(selector, [])), []);
});

test('stored subset remains explicit when more projects are available', () => {
    const selector = createSelector([1]);
    assert.deepEqual(Array.from(loadOptions(selector, [1, 2])), [1]);
});

test('the initial default and an explicit all-project selection are unfiltered', () => {
    assert.equal(loadOptions(createSelector(), [1, 2]), null);
    assert.equal(loadOptions(createSelector([1, 2]), [1, 2]), null);
});

test('clearing the selection emits an empty array, not an unrestricted filter', () => {
    const selector = createSelector();
    loadOptions(selector, []);
    selector.methods.selectedProjects.call(selector.instance, []);
    assert.deepEqual(Array.from(selector.events.at(-1).value), []);
});
