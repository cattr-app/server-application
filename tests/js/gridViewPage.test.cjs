const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

function loadComponent(timers) {
    const source = fs.readFileSync(
        path.join(__dirname, '../../resources/frontend/core/views/Crud/GridView.vue'),
        'utf8',
    );
    const openTag = '<script>';
    const closeTag = '</script>';
    const start = source.indexOf(openTag);
    const end = source.indexOf(closeTag, start + openTag.length);
    if (start === -1 || end === -1) {
        throw new Error('GridView.vue script block not found');
    }
    const script = source
        .slice(start + openTag.length, end)
        .replace(/^\s*import .*;\s*$/gm, '')
        .replace('export default {', 'const component = {');
    const sandbox = {
        matchMedia: () => ({ matches: false }),
        mapGetters: () => ({}),
        Preloader: {},
        ProjectSelect: {},
        StatusSelect: {},
        UserSelect: {},
        setTimeout: callback => timers.push(callback),
        clearTimeout: () => timers.splice(0),
        parseInt,
        Math,
        Set,
        String,
        Array,
        Object,
    };
    vm.runInNewContext(`${script}\nthis.component = component;`, sandbox);
    return sandbox.component;
}

// Tasks grid: three async select filters, see resources/frontend/core/modules/Tasks/module.init.js.
function createGrid(query = {}) {
    const timers = [];
    const component = loadComponent(timers);
    const requests = [];
    const pushes = [];
    const filterFields = [
        { key: 'project_id', fieldOptions: { type: 'project-select' } },
        { key: 'users.id', fieldOptions: { type: 'user-select' } },
        { key: 'status.id', fieldOptions: { type: 'status-select' } },
    ];
    const grid = {
        $route: {
            name: 'Tasks.crud.tasks',
            query,
            params: {},
            meta: {
                gridData: {
                    columns: [],
                    filters: [{ referenceKey: 'task_name' }],
                    filterFields,
                    service: {
                        getWithFilters: async params => {
                            requests.push(params.page);
                            return {
                                data: {
                                    data: [],
                                    pagination: { total: 50, perPage: 15, currentPage: params.page },
                                },
                            };
                        },
                    },
                },
            },
        },
        $t: key => key,
        $router: { push: route => pushes.push(route) },
    };
    Object.assign(grid, component.data.call(grid));
    for (const [name, method] of Object.entries(component.methods)) {
        grid[name] = method.bind(grid);
    }
    Object.defineProperty(grid, 'filtersLoaded', { get: () => component.computed.filtersLoaded.call(grid) });
    Object.defineProperty(grid, 'visibleFilterFields', { get: () => filterFields });

    // Order used by the selects: `change` with the restored selection, then `loaded`.
    const hydrate = async () => {
        grid.onProjectsChange('project_id', [1, 2]);
        grid.onFilterLoaded('project_id');
        grid.onUserSelectChange('users.id', [3]);
        grid.onFilterLoaded('users.id');
        grid.onStatusesChange('status.id', [4]);
        grid.onFilterLoaded('status.id');
        await flush();
    };
    const runTimers = async () => {
        timers.splice(0).forEach(callback => callback());
        await flush();
    };

    return { grid, requests, pushes, hydrate, runTimers };
}

const flush = () => new Promise(resolve => setImmediate(resolve));

test('page from the query survives initial filter hydration', async () => {
    const { grid, requests, pushes, hydrate, runTimers } = createGrid({ page: '2' });
    await hydrate();
    await runTimers();

    assert.deepEqual(requests, [2]);
    assert.equal(grid.page, 2);
    assert.equal(grid.queryParams.page, 2);
    assert.deepEqual(pushes, []);
    assert.equal(JSON.stringify(grid.queryParams.where['project_id']), '["=",[1,2]]');
});

test('an invalid page query falls back to the first page', async () => {
    const { requests, hydrate } = createGrid({ page: 'abc' });
    await hydrate();
    assert.deepEqual(requests, [1]);
});

test('re-emitting an unchanged selection keeps the page', async () => {
    const { grid, requests, pushes, hydrate, runTimers } = createGrid({ page: '3' });
    await hydrate();
    grid.onProjectsChange('project_id', [2, 1]);
    await runTimers();

    assert.deepEqual(requests, [3]);
    assert.equal(grid.queryParams.page, 3);
    assert.deepEqual(pushes, []);
});

test('a user filter change resets to the first page and syncs the URL', async () => {
    const { grid, requests, pushes, hydrate, runTimers } = createGrid({ page: '2' });
    await hydrate();
    grid.onStatusesChange('status.id', [4, 5]);
    await runTimers();

    assert.equal(grid.queryParams.page, 1);
    assert.deepEqual(requests, [2, 1]);
    assert.equal(pushes.length, 1);
    assert.equal(pushes[0].query.page, 1);
});

test('a user filter change on the first page does not push a duplicate route', async () => {
    const { grid, pushes, hydrate, runTimers } = createGrid();
    await hydrate();
    grid.onUserSelectChange('users.id', []);
    await runTimers();

    assert.deepEqual(pushes, []);
    assert.equal(grid.queryParams.where['users.id'], undefined);
});

test('a selection mutated in place still counts as a change', async () => {
    const { grid, requests, pushes, runTimers } = createGrid({ page: '2' });
    const userIDs = [3];
    grid.onProjectsChange('project_id', [1, 2]);
    grid.onFilterLoaded('project_id');
    grid.onUserSelectChange('users.id', userIDs);
    grid.onFilterLoaded('users.id');
    grid.onStatusesChange('status.id', [4]);
    grid.onFilterLoaded('status.id');
    await flush();

    userIDs.push(5);
    grid.onUserSelectChange('users.id', userIDs);
    await runTimers();

    assert.deepEqual(requests, [2, 1]);
    assert.equal(pushes.length, 1);
    assert.equal(JSON.stringify(grid.queryParams.where['users.id']), '["=",[3,5]]');
});
