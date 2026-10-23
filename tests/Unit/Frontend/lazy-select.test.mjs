import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { registerHooks } from 'node:module';
import { setImmediate as flushPromises } from 'node:timers/promises';
import { test } from 'node:test';
import Vue from 'vue';
import { compileTemplate, parse } from 'vue/compiler-sfc';
import VueI18n from 'vue-i18n';

// Run with the repository's Node 24 runtime:
// node --test tests/Unit/Frontend/lazy-select.test.mjs
// Compile the actual SFC in memory; no browser, build or generated files are needed.
const componentUrl = new URL(
    '../../../resources/frontend/core/modules/TimeIntervals/components/LazySelect.vue',
    import.meta.url,
);
const descriptor = parse({ source: readFileSync(componentUrl, 'utf8'), filename: componentUrl.pathname });
const componentHook = registerHooks({
    resolve(specifier, context, nextResolve) {
        if (specifier === 'lodash/debounce') {
            return nextResolve('lodash/debounce.js', context);
        }
        return nextResolve(specifier, context);
    },
    load(url, context, nextLoad) {
        if (url === componentUrl.href) {
            return { format: 'module', source: descriptor.script.content, shortCircuit: true };
        }
        return nextLoad(url, context);
    },
});

let LazySelect;
try {
    ({ default: LazySelect } = await import(componentUrl.href));
} finally {
    componentHook.deregister();
}
// Match Webpack's default-import interop for vue-select's CommonJS bundle.
LazySelect.components.vSelect = LazySelect.components.vSelect.default ?? LazySelect.components.vSelect;
const compiled = compileTemplate({ source: descriptor.template.content, filename: componentUrl.pathname });
assert.deepEqual(compiled.errors, [], 'the component template must compile');
Object.assign(LazySelect, new Function(`${compiled.code}; return { render, staticRenderFns };`)());

Vue.use(VueI18n);
const messages = Object.fromEntries(
    ['en', 'ru'].map(locale => [
        locale,
        JSON.parse(readFileSync(new URL(`../locales/${locale}.json`, componentUrl), 'utf8')),
    ]),
);

function page(tasks = [], currentPage = 1, totalPages = 1) {
    return { data: { data: tasks, pagination: { currentPage, totalPages } } };
}

function setup(t, { userID, locale = 'en', get = () => Promise.resolve(page()) } = {}) {
    t.mock.timers.enable({ apis: ['setTimeout', 'Date'], now: 1000 });
    const getWithFilters = t.mock.fn(get);
    const inputHandler = t.mock.fn();
    const vm = new Vue({
        ...LazySelect,
        propsData: { service: { getWithFilters }, inputHandler, userID },
        i18n: new VueI18n({ locale, messages }),
    });
    // The component's observer is its only DOM boundary. Exercise its real mounted hook
    // with a per-test observer, while rendering Vue VNodes without mounting a browser DOM.
    const observer = {
        callback: null,
        observe: t.mock.fn(),
        disconnect: t.mock.fn(),
    };
    const observerDescriptor = Object.getOwnPropertyDescriptor(globalThis, 'IntersectionObserver');
    Object.defineProperty(globalThis, 'IntersectionObserver', {
        configurable: true,
        value: class {
            constructor(callback) {
                observer.callback = callback;
                return observer;
            }
        },
    });
    try {
        LazySelect.mounted.call(vm);
    } finally {
        if (observerDescriptor) {
            Object.defineProperty(globalThis, 'IntersectionObserver', observerDescriptor);
        } else {
            delete globalThis.IntersectionObserver;
        }
    }
    t.after(() => vm.$destroy());
    return { vm, getWithFilters, inputHandler, observer };
}

async function search(vm, query = 'task') {
    vm.onSearch(query);
    await vm.debouncedSearch.flush();
}

function selectNode(vm) {
    return vm._render().children.find(node => node.componentOptions);
}

function noOptions(vm) {
    return selectNode(vm).componentOptions.children.find(node => node.data?.slot === 'no-options');
}

function footer(vm) {
    return selectNode(vm)
        .data.scopedSlots['list-footer']()
        .find(node => node.tag === 'li');
}

function textContent(node) {
    return [node.text || '', ...(node.children || []).map(textContent)].join('').trim();
}

for (const query of ['', '   ', 'ab', '  ab  ']) {
    test(`search ${JSON.stringify(query)} does not request tasks below the trimmed three-character minimum`, async t => {
        const { vm, getWithFilters } = setup(t);
        vm.onSearch(query);
        t.mock.timers.tick(1000);
        await flushPromises();

        assert.equal(getWithFilters.mock.callCount(), 0);
        assert.deepEqual(vm.options, []);
        assert.equal(vm.isLoading, false);
    });
}

test('a trimmed three-character query requests page one with the selected user and project relation', async t => {
    const { vm, getWithFilters } = setup(t, { userID: 42 });
    await search(vm, '  abc  ');

    assert.deepEqual(getWithFilters.mock.calls[0].arguments, [
        { search: { query: 'abc', fields: ['task_name'] }, with: ['project'], page: 1, where: { 'users.id': 42 } },
    ]);
    assert.equal(vm.isLoading, false);
});

test('search without a selected user omits the user filter', async t => {
    const { vm, getWithFilters } = setup(t);
    await search(vm);

    assert.deepEqual(getWithFilters.mock.calls[0].arguments, [
        { search: { query: 'task', fields: ['task_name'] }, with: ['project'], page: 1 },
    ]);
});

test('rapid searches wait 350 milliseconds after the last input and request only its query', async t => {
    const { vm, getWithFilters } = setup(t);
    vm.onSearch('first');
    t.mock.timers.tick(200);
    vm.onSearch('latest');
    t.mock.timers.tick(349);
    assert.equal(getWithFilters.mock.callCount(), 0);

    t.mock.timers.tick(1);
    await flushPromises();
    assert.equal(getWithFilters.mock.callCount(), 1);
    assert.equal(getWithFilters.mock.calls[0].arguments[0].search.query, 'latest');
});

test('shortening a query cancels its pending debounced request', async t => {
    const { vm, getWithFilters } = setup(t);
    vm.onSearch('valid');
    t.mock.timers.tick(349);
    vm.onSearch('ab');
    t.mock.timers.tick(1000);
    await flushPromises();

    assert.equal(getWithFilters.mock.callCount(), 0);
    assert.equal(vm.hasNextPage, false);
});

test('pages append in server order, preserve task data and project labels, and allow selecting a later task', async t => {
    const tasks = [
        { id: 1, task_name: 'Same title', project: { id: 10, name: 'Alpha' }, description: 'Keep this' },
        { id: 2, task_name: 'Same title', project: { id: 20, name: 'Beta' } },
        { id: 3, task_name: 'No project' },
        { id: 4, task_name: 'Null project', project: null },
    ];
    const responses = [page(tasks.slice(0, 1), 1, 3), page(tasks.slice(1, 3), 2, 3), page(tasks.slice(3), 3, 3)];
    const { vm, getWithFilters, inputHandler } = setup(t, {
        get: () => Promise.resolve(responses.shift()),
    });
    await search(vm);
    assert.equal(vm.hasNextPage, true);
    await vm.loadNextPage(vm.requestId);
    assert.equal(vm.hasNextPage, true);
    await vm.loadNextPage(vm.requestId);

    assert.deepEqual(
        getWithFilters.mock.calls.map(call => call.arguments[0].page),
        [1, 2, 3],
    );
    assert.deepEqual(
        vm.options,
        tasks.map((task, index) => ({
            ...task,
            label: ['Same title (Alpha)', 'Same title (Beta)', 'No project', 'Null project'][index],
        })),
    );
    assert.ok(
        tasks.every(task => !Object.hasOwn(task, 'label')),
        'service responses must not be mutated',
    );
    selectNode(vm).componentOptions.listeners.input(vm.options[1]);
    assert.deepEqual(inputHandler.mock.calls[0].arguments, [2]);
    assert.equal(vm.hasNextPage, false);
    assert.equal(footer(vm), undefined);
    await vm.loadNextPage(vm.requestId);
    assert.equal(getWithFilters.mock.callCount(), 3, 'the final page must stop further requests');
});

for (const totalPages of [0, 1]) {
    test(`an empty result with ${totalPages} total pages ends pagination`, async t => {
        const { vm, getWithFilters } = setup(t, { get: () => Promise.resolve(page([], 1, totalPages)) });
        await search(vm);
        await vm.loadNextPage(vm.requestId);

        assert.deepEqual(vm.options, []);
        assert.equal(vm.hasNextPage, false);
        assert.equal(vm.loadFailed, false);
        assert.equal(vm.isLoading, false);
        assert.equal(getWithFilters.mock.callCount(), 1);
        assert.equal(footer(vm), undefined);
    });
}

test('repeated page loads and intersections share one pending request', async t => {
    const pending = Promise.withResolvers();
    const { vm, getWithFilters, observer } = setup(t, { get: () => pending.promise });
    vm.onSearch('task');
    const loading = vm.debouncedSearch.flush();
    assert.equal(vm.isLoading, true);
    await vm.loadNextPage(vm.requestId);
    observer.callback([{ isIntersecting: true }]);
    assert.equal(getWithFilters.mock.callCount(), 1);

    pending.resolve(page([{ id: 1, task_name: 'Task' }], 1, 2));
    await loading;
    assert.equal(vm.isLoading, false);
    assert.equal(vm.options.length, 1);
});

test('a new search clears previous pages and starts again at page one', async t => {
    const { vm, getWithFilters } = setup(t, {
        get: filters => Promise.resolve(page([{ id: filters.page, task_name: 'Old' }], filters.page, 4)),
    });
    await search(vm, 'old');
    await vm.loadNextPage(vm.requestId);
    vm.onSearch('new');

    assert.deepEqual(vm.options, []);
    assert.equal(vm.currentPage, 0);
    assert.equal(vm.totalPages, 0);
    assert.equal(vm.hasNextPage, false);
    assert.equal(vm.isLoading, false);
    await vm.debouncedSearch.flush();
    assert.equal(getWithFilters.mock.calls.at(-1).arguments[0].page, 1);
    assert.equal(getWithFilters.mock.calls.at(-1).arguments[0].search.query, 'new');
});

for (const outcome of ['resolve', 'reject']) {
    for (const oldFinishesFirst of [true, false]) {
        test(`an obsolete query ${outcome} is ignored when it finishes ${oldFinishesFirst ? 'before' : 'after'} the new request`, async t => {
            const old = Promise.withResolvers();
            const current = Promise.withResolvers();
            const error = t.mock.method(console, 'error', () => {});
            const { vm, getWithFilters, observer } = setup(t, { get: () => old.promise });
            vm.onSearch('old');
            const oldLoad = vm.debouncedSearch.flush();
            const oldRequestId = vm.requestId;
            getWithFilters.mock.mockImplementation(() => current.promise);
            vm.onSearch('new');
            const newLoad = vm.debouncedSearch.flush();
            const disconnects = observer.disconnect.mock.callCount();
            const finishOld = async () => {
                old[outcome](
                    outcome === 'resolve' ? page([{ id: 1, task_name: 'Old' }], 1, 9) : new Error('Old failure'),
                );
                await oldLoad;
            };

            if (oldFinishesFirst) {
                await finishOld();
                assert.deepEqual(vm.options, []);
                assert.equal(vm.currentPage, 0);
                assert.equal(vm.totalPages, 0);
                assert.equal(vm.isLoading, true, 'stale completion must not hide the current spinner');
                assert.equal(observer.disconnect.mock.callCount(), disconnects);
            }
            current.resolve(page([{ id: 2, task_name: 'New' }], 1, 2));
            await newLoad;
            if (!oldFinishesFirst) {
                await finishOld();
            }

            assert.deepEqual(
                vm.options.map(task => task.id),
                [2],
            );
            assert.equal(vm.currentPage, 1);
            assert.equal(vm.totalPages, 2);
            assert.equal(vm.isLoading, false);
            assert.equal(vm.loadFailed, false);
            assert.equal(error.mock.callCount(), 0);
            await vm.loadNextPage(oldRequestId);
            assert.equal(getWithFilters.mock.callCount(), 2, 'obsolete page callbacks cannot start another request');
        });
    }
}

test('shortening a query while loading prevents the old response from repopulating options', async t => {
    const pending = Promise.withResolvers();
    const { vm, getWithFilters } = setup(t, { get: () => pending.promise });
    vm.onSearch('task');
    const loading = vm.debouncedSearch.flush();
    vm.onSearch('ab');
    pending.resolve(page([{ id: 1, task_name: 'Old' }], 1, 3));
    await loading;
    t.mock.timers.tick(1000);
    await vm.loadNextPage(vm.requestId);

    assert.deepEqual(vm.options, []);
    assert.equal(vm.currentPage, 0);
    assert.equal(vm.totalPages, 0);
    assert.equal(vm.isLoading, false);
    assert.equal(getWithFilters.mock.callCount(), 1);
});

for (const userID of [22, undefined]) {
    test(`${userID ? 'changing' : 'clearing'} the user restarts the same query and discards the previous user's response`, async t => {
        const old = Promise.withResolvers();
        const current = Promise.withResolvers();
        const { vm, getWithFilters } = setup(t, { userID: 11, get: () => old.promise });
        vm.onSearch('task');
        const oldLoad = vm.debouncedSearch.flush();
        getWithFilters.mock.mockImplementation(() => current.promise);
        vm.userID = userID;
        await vm.$nextTick();
        const newLoad = vm.debouncedSearch.flush();

        assert.deepEqual(getWithFilters.mock.calls[0].arguments[0].where, { 'users.id': 11 });
        assert.deepEqual(getWithFilters.mock.calls[1].arguments[0], {
            search: { query: 'task', fields: ['task_name'] },
            with: ['project'],
            page: 1,
            ...(userID ? { where: { 'users.id': userID } } : {}),
        });
        old.resolve(page([{ id: 1, task_name: 'Old user' }]));
        await oldLoad;
        assert.deepEqual(vm.options, []);
        assert.equal(vm.isLoading, true);
        current.resolve(page([{ id: 2, task_name: 'Current user' }]));
        await newLoad;
        assert.deepEqual(
            vm.options.map(task => task.id),
            [2],
        );
    });
}

test('a user change resets the debounce window and cancels the previous user request before it starts', async t => {
    const { vm, getWithFilters } = setup(t, { userID: 11 });
    vm.onSearch('task');
    t.mock.timers.tick(200);
    vm.userID = 22;
    await vm.$nextTick();
    t.mock.timers.tick(349);
    assert.equal(getWithFilters.mock.callCount(), 0);
    t.mock.timers.tick(1);
    await flushPromises();
    assert.equal(getWithFilters.mock.callCount(), 1);
    assert.deepEqual(getWithFilters.mock.calls[0].arguments[0].where, { 'users.id': 22 });
});

test('opening the dropdown observes its footer after the next tick and intersections load the next page', async t => {
    const { vm, getWithFilters, observer } = setup(t, {
        get: filters => Promise.resolve(page([{ id: filters.page, task_name: 'Task' }], filters.page, 3)),
    });
    await search(vm);
    vm.onOpen();
    assert.equal(observer.observe.mock.callCount(), 0);
    const sentinel = { name: 'footer element' };
    vm.$refs.load = sentinel;
    await vm.$nextTick();
    assert.strictEqual(observer.observe.mock.calls[0].arguments[0], sentinel);
    observer.callback([{ isIntersecting: false }]);
    assert.equal(getWithFilters.mock.callCount(), 1);

    const disconnects = observer.disconnect.mock.callCount();
    observer.callback([{ isIntersecting: true }]);
    assert.equal(observer.disconnect.mock.callCount(), disconnects + 1);
    assert.equal(getWithFilters.mock.calls.at(-1).arguments[0].page, 2);
    await flushPromises();
    assert.equal(observer.observe.mock.callCount(), 2, 'a successful page reattaches the observer');
});

for (const condition of ['closed', 'no footer', 'last page', 'failed', 'no observer']) {
    test(`pagination does not observe a footer when ${condition}`, t => {
        const { vm, observer } = setup(t);
        vm.isOpen = condition !== 'closed';
        vm.currentPage = 1;
        vm.totalPages = condition === 'last page' ? 1 : 2;
        vm.loadFailed = condition === 'failed';
        if (condition !== 'no footer') vm.$refs.load = {};
        if (condition === 'no observer') vm.observer = null;

        vm.observeNextPage();
        assert.equal(observer.observe.mock.callCount(), 0);
    });
}

test('closing during a page request keeps the observer detached until the dropdown reopens', async t => {
    const pending = Promise.withResolvers();
    const { vm, observer } = setup(t, { get: () => pending.promise });
    vm.$refs.load = {};
    vm.onOpen();
    await vm.$nextTick();
    vm.onSearch('task');
    const loading = vm.debouncedSearch.flush();
    const disconnects = observer.disconnect.mock.callCount();
    vm.onClose();
    assert.equal(vm.isOpen, false);
    assert.equal(observer.disconnect.mock.callCount(), disconnects + 1);
    pending.resolve(page([{ id: 1, task_name: 'Task' }], 1, 2));
    await loading;
    assert.equal(observer.observe.mock.callCount(), 0);
    vm.onOpen();
    await vm.$nextTick();
    assert.equal(observer.observe.mock.callCount(), 1);
});

test('a failed first page stops automatic requests and an explicit retry recovers page one', async t => {
    const failure = new Error('Network failure');
    const error = t.mock.method(console, 'error', () => {});
    const { vm, getWithFilters, observer } = setup(t, { get: () => Promise.reject(failure) });
    await search(vm);

    assert.equal(vm.loadFailed, true);
    assert.equal(vm.isLoading, false);
    assert.equal(vm.currentPage, 0);
    assert.equal(vm.hasNextPage, false);
    assert.deepEqual(vm.options, []);
    assert.deepEqual(error.mock.calls[0].arguments, ['Failed to load tasks for time interval', failure]);
    observer.callback([{ isIntersecting: true }]);
    await vm.loadNextPage(vm.requestId);
    assert.equal(getWithFilters.mock.callCount(), 1);

    const recovery = Promise.withResolvers();
    getWithFilters.mock.mockImplementation(() => recovery.promise);
    vm.retryLoad();
    vm.retryLoad();
    assert.equal(vm.loadFailed, false);
    assert.equal(vm.isLoading, true);
    assert.equal(getWithFilters.mock.callCount(), 2, 'repeated retry clicks cannot duplicate a request');
    assert.equal(getWithFilters.mock.calls[1].arguments[0].page, 1);
    recovery.resolve(page([{ id: 1, task_name: 'Recovered' }]));
    await flushPromises();
    assert.equal(vm.isLoading, false);
    assert.equal(vm.loadFailed, false);
    assert.deepEqual(
        vm.options.map(task => task.id),
        [1],
    );
});

test('retrying a failed second page preserves loaded options and requests that same page again', async t => {
    t.mock.method(console, 'error', () => {});
    const first = { id: 1, task_name: 'First', project: { name: 'Project' } };
    const { vm, getWithFilters, observer } = setup(t, { get: () => Promise.resolve(page([first], 1, 2)) });
    vm.$refs.load = {};
    vm.onOpen();
    await search(vm);
    getWithFilters.mock.mockImplementation(() => Promise.reject(new Error('Page two failed')));
    await vm.loadNextPage(vm.requestId);

    assert.deepEqual(vm.options, [{ ...first, label: 'First (Project)' }]);
    assert.equal(vm.currentPage, 1);
    assert.equal(vm.totalPages, 2);
    assert.equal(vm.hasNextPage, false);
    const observations = observer.observe.mock.callCount();
    observer.callback([{ isIntersecting: true }]);
    assert.equal(getWithFilters.mock.callCount(), 2);
    assert.equal(observer.observe.mock.callCount(), observations);

    getWithFilters.mock.mockImplementation(() => Promise.resolve(page([{ id: 2, task_name: 'Second' }], 2, 2)));
    const retry = footer(vm).children.find(node => node.tag === 'button');
    const stopPropagation = t.mock.fn();
    retry.data.on.click({ stopPropagation });
    await flushPromises();

    assert.equal(stopPropagation.mock.callCount(), 1);
    assert.deepEqual(
        getWithFilters.mock.calls.map(call => call.arguments[0].page),
        [1, 2, 2],
    );
    assert.deepEqual(
        vm.options.map(task => task.id),
        [1, 2],
    );
    assert.equal(vm.loadFailed, false);
    assert.equal(vm.isLoading, false);
    assert.equal(footer(vm), undefined);
});

test('a retry that also fails remains stopped until another explicit retry or a new search', async t => {
    t.mock.method(console, 'error', () => {});
    const { vm, getWithFilters } = setup(t, { get: () => Promise.reject(new Error('Offline')) });
    await search(vm);
    vm.retryLoad();
    await flushPromises();
    t.mock.timers.tick(10000);
    assert.equal(vm.loadFailed, true);
    assert.equal(vm.isLoading, false);
    assert.equal(getWithFilters.mock.callCount(), 2);

    getWithFilters.mock.mockImplementation(() => Promise.resolve(page([{ id: 3, task_name: 'New query' }])));
    vm.onSearch('new query');
    assert.equal(vm.loadFailed, false);
    await vm.debouncedSearch.flush();
    assert.equal(getWithFilters.mock.callCount(), 3);
    assert.deepEqual(
        vm.options.map(task => task.id),
        [3],
    );
});

test('retry without a failure does not start a request', t => {
    const { vm, getWithFilters } = setup(t);
    vm.retryLoad();
    assert.equal(getWithFilters.mock.callCount(), 0);
});

test('destroying the component cancels debounce and disconnects the observer', async t => {
    const { vm, getWithFilters, observer } = setup(t);
    vm.onSearch('task');
    const disconnects = observer.disconnect.mock.callCount();
    vm.$destroy();
    assert.equal(observer.disconnect.mock.callCount(), disconnects + 1);
    t.mock.timers.tick(1000);
    await flushPromises();
    assert.equal(getWithFilters.mock.callCount(), 0);
});

for (const outcome of ['resolve', 'reject']) {
    test(`a request that ${outcome}s after destruction cannot change results, report errors or reattach the observer`, async t => {
        const pending = Promise.withResolvers();
        const error = t.mock.method(console, 'error', () => {});
        const { vm, observer } = setup(t, { get: () => pending.promise });
        vm.onSearch('task');
        const loading = vm.debouncedSearch.flush();
        vm.$destroy();
        const disconnects = observer.disconnect.mock.callCount();
        pending[outcome](
            outcome === 'resolve' ? page([{ id: 1, task_name: 'Late' }], 1, 3) : new Error('Late failure'),
        );
        await loading;

        assert.deepEqual(vm.options, []);
        assert.equal(vm.currentPage, 0);
        assert.equal(vm.totalPages, 0);
        assert.equal(vm.loadFailed, false);
        assert.equal(error.mock.callCount(), 0);
        assert.equal(observer.disconnect.mock.callCount(), disconnects);
        assert.equal(observer.observe.mock.callCount(), 0);
    });
}

test('the rendered selector retains server matches, binds loading, and forwards search/open/close events', async t => {
    const pending = Promise.withResolvers();
    const { vm } = setup(t, { get: () => pending.promise });
    const listeners = selectNode(vm).componentOptions.listeners;
    listeners.search('abc');
    const loading = vm.debouncedSearch.flush();
    assert.equal(selectNode(vm).componentOptions.propsData.loading, true);
    listeners.open();
    assert.equal(vm.isOpen, true);
    listeners.close();
    assert.equal(vm.isOpen, false);

    pending.resolve(page([{ id: 1, task_name: 'Server matched a different spelling' }]));
    await loading;
    const props = selectNode(vm).componentOptions.propsData;
    assert.equal(props.filterable, false);
    assert.equal(props.loading, false);
    assert.strictEqual(props.options, vm.options);
    assert.equal(props.options[0].label, 'Server matched a different spelling');
    assert.equal(props.clearable, false);
    assert.equal(props.label, 'label');
});

for (const locale of ['en', 'ru']) {
    test(`${locale} renders translated empty, loading, and retry states in the appropriate slots`, async t => {
        t.mock.method(console, 'error', () => {});
        const { vm, getWithFilters } = setup(t, { locale });
        const translations = messages[locale].time_intervals.task_select;
        for (const key of ['loading', 'load_failed', 'retry']) {
            assert.equal(typeof translations[key], 'string');
            assert.ok(translations[key].trim(), `${locale}.${key} must be translated`);
        }
        assert.equal(textContent(noOptions(vm)), translations.no_options);
        assert.equal(footer(vm), undefined);

        getWithFilters.mock.mockImplementation(() => Promise.reject(new Error('First page failed')));
        await search(vm);
        const emptyFailure = noOptions(vm);
        assert.ok(textContent(emptyFailure).includes(translations.load_failed));
        const retry = emptyFailure.children.find(node => node.tag === 'button');
        assert.equal(textContent(retry), translations.retry);
        assert.equal(retry.data.attrs.type, 'button');
        assert.equal(footer(vm), undefined);

        getWithFilters.mock.mockImplementation(() => Promise.resolve(page([{ id: 1, task_name: 'Task' }], 1, 2)));
        retry.data.on.click({ stopPropagation() {} });
        await flushPromises();
        assert.equal(textContent(footer(vm)), translations.loading);

        getWithFilters.mock.mockImplementation(() => Promise.reject(new Error('Later page failed')));
        await vm.loadNextPage(vm.requestId);
        assert.ok(textContent(footer(vm)).includes(translations.load_failed));
        assert.ok(textContent(footer(vm)).includes(translations.retry));
        assert.ok(!textContent(footer(vm)).includes(translations.loading));
    });
}
