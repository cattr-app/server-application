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
