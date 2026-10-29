const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

function createSelector({ taskId = null, userID = 1, tasks = new Map(), search = async () => ({ data: { data: [] } }) } = {}) {
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
    const { instance } = createSelector({
        tasks: new Map([[9, task]]),
        search: async () => ({ data: { data: [] } }),
    });
    await instance.hydrateTask(9, 1);
    assert.equal(instance.value, 9);
    instance.userID = 2;
    instance.inputHandler(null);
    await instance.hydrateTask(9, 2, true);
    assert.equal(instance.value, null);
    assert.equal(instance.selectionError, 'time_intervals.task_select.unavailable');
});

test('standalone selection does not require a preselected ID', () => {
    const { component, instance } = createSelector();
    assert.equal(instance.preselectedId, null);
    assert.equal(component.computed.selectedOption.call(instance), null);
    instance.onSelect({ id: 7, label: 'Manual task' });
    assert.equal(instance.value, 7);
    assert.equal(component.computed.selectedOption.call(instance).label, 'Manual task');
});
