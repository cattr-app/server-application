// Pages of the smoke checklist (migration plan §6), shared by the smoke and visual suites.
// Ids and names come from the deterministic seed (tests/e2e/support/seed.php).
export const SEED = {
    project: { id: 1, name: 'Quis dignissimos beatae quis.' },
    task: { id: 1, name: 'Odio dolores aliquam ut.' },
    user: { id: 2, name: 'Lelah Bogan', email: 'projectManager@example.com' },
    interval: { id: 1 },
    priority: { id: 1, name: 'Low' },
    status: { id: 1, name: 'Open' },
};

const title = text => page => page.locator('.page-title, .crud__title, .control-item.title', { hasText: text }).first();
const tableRow = text => page => page.locator('.at-table__tbody tr', { hasText: text }).first();

/**
 * @typedef {object} AppPage
 * @property {string} id         stable name (test title and screenshot file name)
 * @property {string} path       route
 * @property {(page) => import('@playwright/test').Locator} ready  key element that must be visible
 * @property {boolean} [guest]   opened without a session
 * @property {boolean} [visual]  part of the visual suite
 * @property {string[]} [mask]   extra selectors to mask in screenshots (see visual/pages.spec.js for the global ones)
 * @property {string} [knownBroken] the page is broken on main@bfafed73: the smoke test is expected to fail
 *                               (test.fail) and reports "unexpectedly passed" once the migration fixes it
 */

/** @type {AppPage[]} */
export const PAGES = [
    // Auth
    { id: 'auth-login', path: '/auth/login', guest: true, visual: true, ready: page => page.locator('.login__form') },
    {
        id: 'auth-password-reset',
        path: '/auth/password/reset',
        guest: true,
        visual: true,
        ready: page => page.getByRole('button', { name: /reset|send|submit/i }),
    },
    { id: 'auth-register', path: '/auth/register', guest: true, ready: page => page.locator('#app > *').first() },
    {
        id: 'auth-desktop-login',
        path: '/auth/desktop/login',
        guest: true,
        ready: page => page.locator('#app > *').first(),
    },

    // Dashboard
    {
        id: 'dashboard-timeline',
        path: '/dashboard/timeline',
        visual: true,
        ready: page => page.locator('.intervals'),
    },
    {
        id: 'dashboard-team',
        path: '/dashboard/team',
        visual: true,
        ready: page => page.locator('.team_sidebar').getByText(SEED.user.name),
    },

    // Projects
    { id: 'projects-grid', path: '/projects', visual: true, ready: tableRow(SEED.project.name) },
    {
        id: 'project-view',
        path: `/projects/view/${SEED.project.id}`,
        visual: true,
        ready: title(SEED.project.name),
    },
    {
        id: 'project-edit',
        path: `/projects/edit/${SEED.project.id}`,
        visual: true,
        ready: page => page.locator('.crud__edit-view input').first(),
    },
    { id: 'project-new', path: '/projects/new', ready: page => page.locator('.crud__edit-view') },
    {
        id: 'project-members',
        path: `/projects/${SEED.project.id}/members`,
        visual: true,
        ready: page => page.locator('.project-members-form'),
    },
    {
        id: 'project-kanban',
        path: `/projects/${SEED.project.id}/tasks/kanban`,
        visual: true,
        ready: page => page.locator('.drag-item', { hasText: SEED.task.name }),
    },
    { id: 'project-groups', path: '/project-groups', ready: title('Groups') },
    { id: 'project-group-new', path: '/project-groups/new', ready: page => page.locator('.crud__edit-view') },

    // Tasks
    { id: 'tasks-grid', path: '/tasks', visual: true, ready: tableRow(SEED.task.name) },
    { id: 'task-view', path: `/tasks/view/${SEED.task.id}`, visual: true, ready: title(SEED.task.name) },
    {
        id: 'task-edit',
        path: `/tasks/edit/${SEED.task.id}`,
        visual: true,
        ready: page => page.locator('.crud__edit-view input').first(),
    },
    { id: 'task-new', path: '/tasks/new', ready: page => page.locator('.crud__edit-view') },
    {
        id: 'task-relations',
        path: `/tasks/${SEED.task.id}/relations`,
        ready: page => page.locator('.at-container').first(),
    },

    // Time intervals
    {
        id: 'time-interval-view',
        path: `/time-intervals/view/${SEED.interval.id}`,
        ready: page => page.locator('.crud__item-view'),
        // Blank page on Vue 2: TypeError in the view (see KNOWN_VUE2 in console-allowlist.js).
        knownBroken: 'time interval view renders nothing on main@bfafed73 (TypeError, setting "from")',
    },
    { id: 'time-interval-new', path: '/time-intervals/new', ready: page => page.locator('.crud__edit-view') },

    // Reports
    { id: 'screenshots', path: '/screenshots', visual: true, ready: page => page.locator('.screenshot').first() },
    {
        id: 'calendar',
        path: '/calendar',
        visual: true,
        ready: page => page.locator('.calendar svg').filter({ visible: true }).first(),
    },
    { id: 'gantt', path: `/gantt/${SEED.project.id}`, ready: page => page.locator('.gantt__chart canvas') },
    {
        id: 'report-projects',
        path: '/report/projects',
        visual: true,
        ready: page => page.locator('.project__title', { hasText: SEED.project.name }),
    },
    { id: 'report-time-use', path: '/report/time-use', visual: true, ready: title('Time Use Report') },
    {
        id: 'report-planned-time',
        path: '/report/planned-time',
        visual: true,
        ready: page => page.locator('.project__title', { hasText: SEED.project.name }),
    },
    { id: 'report-universal', path: '/report/universal', ready: title('Universal report') },
    { id: 'report-universal-create', path: '/report/universal/create', ready: title('Universal report') },
    { id: 'offline-sync', path: '/offline-sync', ready: title('Offline Sync') },

    // Company settings
    // The order of the company settings menu items differs between page loads on main@bfafed73
    // (Statuses/Invitations swap), so the menu is masked in the visual suite.
    {
        id: 'company-general',
        path: '/company/general',
        visual: true,
        mask: ['.settings__menu'],
        ready: title('General'),
    },
    {
        id: 'company-users',
        path: '/company/users',
        visual: true,
        mask: ['.settings__menu'],
        ready: tableRow(SEED.user.email),
    },
    {
        id: 'company-user-view',
        path: `/company/users/view/${SEED.user.id}`,
        ready: page => page.locator('.crud__item-view').getByText(SEED.user.email),
    },
    {
        id: 'company-user-edit',
        path: `/company/users/edit/${SEED.user.id}`,
        ready: page => page.locator('.crud__edit-view input').first(),
    },
    { id: 'company-user-new', path: '/company/users/new', ready: page => page.locator('.crud__edit-view') },
    { id: 'company-invitations', path: '/company/invitations', ready: title('Invitations') },
    { id: 'company-invitation-new', path: '/company/invitations/new', ready: page => page.locator('.crud__edit-view') },
    { id: 'company-priorities', path: '/company/priorities', ready: tableRow(SEED.priority.name) },
    {
        id: 'company-priority-edit',
        path: `/company/priorities/edit/${SEED.priority.id}`,
        ready: page => page.locator('.crud__edit-view input').first(),
    },
    {
        id: 'company-statuses',
        path: '/company/statuses',
        visual: true,
        mask: ['.settings__menu'],
        ready: tableRow(SEED.status.name),
    },
    {
        id: 'company-status-edit',
        path: `/company/statuses/edit/${SEED.status.id}`,
        ready: page => page.locator('.crud__edit-view input').first(),
    },

    // Settings and misc
    { id: 'settings-account', path: '/settings/account', visual: true, ready: title('Account') },
    { id: 'about', path: '/about', visual: true, ready: page => page.locator('.about__logo') },
    { id: 'forbidden', path: '/forbidden', ready: page => page.locator('#app .content-wrapper, #app > *').first() },
    { id: 'not-found', path: '/no-such-page', ready: page => page.locator('#app .content-wrapper, #app > *').first() },
];
