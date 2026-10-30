const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

function createSelector({
    taskId = null,
    userID = 1,
    currentUserId = 1,
    tasks = new Map(),
    search = async () => ({ data: { data: [] } }),
    getRecentTaskIds = null,
} = {}) {
    const file = fs.readFileSync(path.join(__dirname, '../../resources/frontend/core/modules/TimeIntervals/components/LazySelect.vue'), 'utf8');
    const script = file.split('<script>')[1].split('</script>')[0]
        .replace(/^\s*import .*;\s*$/gm, '')
        .replace('export default {', 'const component = {');
    const sandbox = {
        debounce: fn => Object.assign(fn, { cancel() {} }),
        vSelect: class {},
        IntersectionObserver: class { disconnect() {} },
        console,
    };
    vm.runInNewContext(script + '\nthis.component = component;', sandbox);
    const component = sandbox.component;
    const events = [];
    const instance = {
        ...component.data(),
        ...component.methods,
        value: null,
        preselectedId: taskId,
        userID,
        currentUserId,
        getRecentTaskIds,
        $refs: {},
        $nextTick: async () => {},
        service: {
            getItem: async id => {
                if (!tasks.has(id)) throw { response: { status: 404 } };
                return { data: { data: tasks.get(id) } };
            },
            getWithFilters: search,
        },
        inputHandler(value) {
            this.value = value;
            events.push(value);
        },
        restartSearch() {},
    };
    // Search-restart tests opt into the real implementation.
    instance.realRestartSearch = component.methods.restartSearch;
    instance.debouncedSearch = Object.assign(requestId => instance.loadNextPage(requestId), { cancel() {} });
    return { component, instance, events };
}

test('preselection hydrates a task outside the first search page', async () => {
    const id = 913;
    const { component, instance, events } = createSelector({
        taskId: String(id),
        tasks: new Map([[id, { id, task_name: 'Task 913', project: { name: 'Alpha' } }]]),
    });
    await instance.hydrateTask(String(id), 1);
    assert.equal(instance.value, id);
    assert.equal(component.computed.selectedOption.call(instance).label, 'Task 913 (Alpha)');
    assert.equal(events.at(-1), id);
});

test('invalid or deleted task cannot leave a selected value', async () => {
    const { instance } = createSelector();
    await instance.hydrateTask('nonsense', 1);
    assert.equal(instance.value, null);
    assert.equal(instance.selectionError, 'time_intervals.task_select.invalid_id');
    await instance.hydrateTask(99, 1);
    assert.equal(instance.value, null);
    assert.equal(instance.selectionError, 'time_intervals.task_select.unavailable');
});

test('invalid URL task ID remains visible when the default user initializes', async () => {
    const { component, instance } = createSelector({ taskId: 'invalid', userID: null });
    await instance.hydrateTask(instance.preselectedId, null);
    assert.equal(instance.selectionError, 'time_intervals.task_select.invalid_id');

    instance.userID = 1;
    component.watch.userID.call(instance, 1);
    assert.equal(instance.value, null);
    assert.equal(instance.selectionError, 'time_intervals.task_select.invalid_id');

    instance.onSelect({ id: 9, label: 'Selected task' });
    assert.equal(instance.value, 9);
    assert.equal(instance.selectionError, null);
});

test('reassignment clears selection when target user is not assigned', async () => {
    const task = { id: 9, task_name: 'Time', project: null };
    const { component, instance } = createSelector({
        tasks: new Map([[9, task]]),
        search: async () => ({ data: { data: [] } }),
    });
    await instance.hydrateTask(9, 1);
    assert.equal(instance.value, 9);
    instance.userID = 2;
    component.watch.userID.call(instance, 2);
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(instance.value, null);
    assert.equal(instance.selectionError, 'time_intervals.task_select.unavailable');
});

test('self-entry preserves a project-visible task without explicit assignment', async () => {
    const task = { id: 9, task_name: 'Project task', project: null };
    const filters = [];
    const { component, instance } = createSelector({
        taskId: '9',
        userID: null,
        currentUserId: 1,
        tasks: new Map([[9, task]]),
        search: async filter => {
            filters.push(filter);
            return { data: { data: [], pagination: { currentPage: 1, totalPages: 1 } } };
        },
    });

    // The parent form sets user_id after its child LazySelect has mounted.
    await instance.hydrateTask(9, null);
    assert.equal(instance.pendingTaskId, 9);
    instance.userID = 1;
    component.watch.userID.call(instance, 1);
    await new Promise(resolve => setImmediate(resolve));

    assert.equal(instance.value, 9);
    assert.equal(instance.selectionError, null);
    assert.equal(filters.length, 0);

    // Searching as oneself must not hide tasks visible via project membership.
    instance.query = 'Project';
    await instance.loadNextPage(instance.requestId);
    assert.equal(filters.length, 1);
    assert.equal(Object.hasOwn(filters[0], 'where'), false);
});

test('assignment checks apply when entering time for another user', async () => {
    const task = { id: 9, task_name: 'Assigned task', project: null };
    const filters = [];
    const { instance } = createSelector({
        userID: 2,
        currentUserId: 1,
        tasks: new Map([[9, task]]),
        search: async filter => {
            filters.push(filter);
            return { data: { data: [task] } };
        },
    });

    await instance.hydrateTask(9, 2);
    assert.equal(instance.value, 9);
    assert.equal(filters.length, 1);
    assert.equal(filters[0].where['users.id'], 2);
});

test('a stale assignment response cannot overwrite a newer user selection', async () => {
    const task = { id: 9, task_name: 'Assigned task', project: null };
    let resolveOldSearch;
    const { component, instance } = createSelector({
        userID: 2,
        currentUserId: 1,
        tasks: new Map([[9, task]]),
        search: filter => {
            if (filter.where['users.id'] === 2) {
                return new Promise(resolve => {
                    resolveOldSearch = resolve;
                });
            }
            return Promise.resolve({ data: { data: [task] } });
        },
    });

    const staleRequest = instance.hydrateTask(9, 2);
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(typeof resolveOldSearch, 'function');

    instance.userID = 3;
    component.watch.userID.call(instance, 3);
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(instance.value, 9);
    assert.equal(instance.selectionError, null);

    resolveOldSearch({ data: { data: [] } });
    await staleRequest;
    assert.equal(instance.value, 9);
    assert.equal(instance.selectionError, null);
});

test('standalone selection does not require a preselected ID', () => {
    const { component, instance } = createSelector();
    assert.equal(instance.preselectedId, null);
    assert.equal(component.computed.selectedOption.call(instance), null);
    instance.onSelect({ id: 7, label: 'Manual task' });
    assert.equal(instance.value, 7);
    assert.equal(component.computed.selectedOption.call(instance).label, 'Manual task');
});

// Values built inside the vm sandbox have foreign prototypes.
const plain = value => JSON.parse(JSON.stringify(value));
const tick = () => new Promise(resolve => setImmediate(resolve));
const page = tasks => ({ data: { data: tasks, pagination: { currentPage: 1, totalPages: 1 } } });

test('empty-query dropdown shows accessible recent tasks in last-used order with fresh labels', async () => {
    const filters = [];
    const { component, instance } = createSelector({
        getRecentTaskIds: () => [3, 1, 2],
        // The API returns tasks in its own order and omits ones the actor cannot see.
        search: async filter => {
            filters.push(filter);
            return page([
                { id: 1, task_name: 'Renamed', project: { name: 'Alpha' } },
                { id: 3, task_name: 'Third', project: null },
            ]);
        },
    });

    component.methods.onOpen.call(instance);
    await tick();

    assert.deepEqual(plain(filters[0].where), { id: ['in', [3, 1, 2]] });
    assert.deepEqual(Array.from(instance.options, task => task.id), [3, 1]);
    assert.equal(instance.options[1].label, 'Renamed (Alpha)');
    assert.equal(instance.showingRecent, true);
});

test('without recent IDs the selector does not request tasks and stays searchable', async () => {
    const filters = [];
    const { instance } = createSelector({
        getRecentTaskIds: () => [],
        search: async filter => {
            filters.push(filter);
            return page([{ id: 5, task_name: 'Found', project: null }]);
        },
    });
    instance.restartSearch = instance.realRestartSearch;

    instance.onOpen();
    await tick();
    assert.equal(filters.length, 0);
    assert.equal(instance.options.length, 0);

    instance.onSearch('Fou');
    await tick();
    assert.equal(filters.length, 1);
    assert.deepEqual(plain(filters[0].search), { query: 'Fou', fields: ['task_name'] });
    assert.deepEqual(Array.from(instance.options, task => task.id), [5]);
    assert.equal(instance.showingRecent, false);
});

test('a failed recent-task request leaves an empty, searchable selector', async () => {
    const { instance } = createSelector({
        getRecentTaskIds: () => [1],
        search: async () => {
            throw { response: { status: 500 } };
        },
    });
    const error = console.error;
    console.error = () => {};
    try {
        instance.onOpen();
        await tick();
    } finally {
        console.error = error;
    }
    assert.equal(instance.options.length, 0);
    assert.equal(instance.loadFailed, false);
    assert.equal(instance.isLoading, false);
});

test('recent tasks for another user are limited to tasks assigned to that user', async () => {
    const filters = [];
    const { component, instance } = createSelector({
        userID: 1,
        currentUserId: 1,
        getRecentTaskIds: () => [7, 8],
        search: async filter => {
            filters.push(filter);
            return page(filter.where['users.id'] === 2 ? [{ id: 8, task_name: 'Shared', project: null }] : []);
        },
    });
    instance.restartSearch = instance.realRestartSearch;
    instance.onOpen();
    await tick();
    assert.equal(Object.hasOwn(filters[0].where, 'users.id'), false);

    instance.userID = 2;
    component.watch.userID.call(instance, 2);
    await tick();
    assert.deepEqual(plain(filters[1].where), { id: ['in', [7, 8]], 'users.id': 2 });
    assert.deepEqual(Array.from(instance.options, task => task.id), [8]);
});

test('a stale recent-task response cannot replace newer search results', async () => {
    let resolveRecent;
    const { instance } = createSelector({
        getRecentTaskIds: () => [1],
        search: filter =>
            filter.search
                ? Promise.resolve(page([{ id: 2, task_name: 'Searched', project: null }]))
                : new Promise(resolve => {
                      resolveRecent = resolve;
                  }),
    });
    instance.restartSearch = instance.realRestartSearch;

    instance.onOpen();
    await tick();
    instance.onSearch('Sea');
    await tick();
    resolveRecent(page([{ id: 1, task_name: 'Recent', project: null }]));
    await tick();

    assert.deepEqual(Array.from(instance.options, task => task.id), [2]);
    assert.equal(instance.showingRecent, false);
});

test('a crafted stored ID is shown only if the authorized API returns it', async () => {
    const { instance } = createSelector({
        getRecentTaskIds: () => [999],
        search: async () => page([]),
    });
    instance.onOpen();
    await tick();
    assert.equal(instance.options.length, 0);
});

test('a preselected task not among recents is still rendered as the selection', async () => {
    const { component, instance } = createSelector({
        taskId: '42',
        tasks: new Map([[42, { id: 42, task_name: 'From task', project: null }]]),
        getRecentTaskIds: () => [1],
        search: async () => page([{ id: 1, task_name: 'Recent', project: null }]),
    });
    await instance.hydrateTask('42', 1);
    instance.onOpen();
    await tick();
    assert.deepEqual(Array.from(instance.options, task => task.id), [1]);
    assert.equal(component.computed.selectedOption.call(instance).label, 'From task');
});
