// Every report opens from the navigation and shows the seeded data (09:30-12:00 UTC on SEED_DATE,
// 5 users x 10 tasks x 30 intervals of 5 minutes) with its chart/graphics element.
import { test, expect } from '../support/test.js';
import { waitForAppIdle } from '../support/app.js';
import { SEED } from '../support/pages.js';
import { SEED_DATE } from '../support/constants.js';
import { apiClient } from '../support/auth.js';
import { chooseAtSelect } from '../support/form.js';

function escapeRegExp(value) {
    return value.replace(/[.*+?^${}()|[\]\\/]/g, '\\$&');
}

/**
 * Open a report through Reports menu, then load it once more from scratch for the data checks.
 * Known bug on main@bfafed73: on client-side navigation the route change cancels the users request of
 * UserSelect, which then stores an empty selection in localStorage ('user-select.users'), so the
 * report shows "No users selected" until the user picks users again.
 */
async function openFromReportsMenu(page, item, path) {
    await page.goto('/about');
    await page.locator('.navbar .at-menu__submenu', { hasText: 'Reports' }).hover();
    await page.locator('.navbar .at-menu__submenu .at-menu__item', { hasText: item }).click();
    await expect(page).toHaveURL(new RegExp(escapeRegExp(path)));
    await waitForAppIdle(page);

    await page.evaluate(() => localStorage.removeItem('user-select.users'));
    await page.goto(path);
    await waitForAppIdle(page);
}

test('navigation: Reports submenu and active item', async ({ page }) => {
    await page.goto('/tasks');
    const link = name => page.locator('.navbar .at-menu__item-link', { hasText: name });
    await expect(link('Tasks')).toHaveClass(/router-link-active/);
    await expect(link('Dashboard')).not.toHaveClass(/router-link-active/);
    const reports = page.locator('.navbar .at-menu__submenu', { hasText: 'Reports' });
    await reports.hover();
    await expect(reports.locator('.at-menu__item')).toHaveText([
        'Planned Time Report',
        'Project report',
        'Time Use Report',
        'Universal report',
    ]);
});

test('project report', async ({ page }) => {
    await openFromReportsMenu(page, 'Project report', '/report/projects');
    await expect(page.locator('.project__title', { hasText: SEED.project.name })).toBeVisible();
    await expect(page.locator('.project__title')).toHaveCount(5);
});

test('time use report', async ({ page }) => {
    await openFromReportsMenu(page, 'Time Use Report', '/report/time-use');
    await expect(page.getByText('125h 0m')).toBeVisible();
    await expect(page.getByText(SEED.user.name, { exact: true }).filter({ visible: true }).first()).toBeVisible();
});

test('planned time report', async ({ page }) => {
    await openFromReportsMenu(page, 'Planned Time Report', '/report/planned-time');
    await expect(page.locator('.project__title', { hasText: SEED.project.name })).toBeVisible();
});

/**
 * The three multi-selects of the universal report form (ObjectsDataSelect, FieldsSelect, Select) share
 * the `.select` markup and follow each other: data objects, report columns, time charts.
 */
const reportForm = page => page.locator('.universal-report__form');
const multiSelect = (page, index) => reportForm(page).locator(':scope > .select').nth(index);

/** Open a multi-select, toggle the given options (by their exact text) and close it again. */
async function toggleOptions(select, options) {
    await select.locator('.select-input').click();
    const dropdown = select.locator('.at-select__dropdown');
    await expect(dropdown).toBeVisible();
    for (const option of options) {
        const item = dropdown.locator('.select-item').filter({ has: select.page().getByText(option, { exact: true }) });
        await expect(item).toHaveCount(1);
        const wasActive = (await item.getAttribute('class')).split(' ').includes('active');
        await item.click();
        if (wasActive) {
            await expect(item).not.toHaveClass(/\bactive\b/);
        } else {
            await expect(item).toHaveClass(/\bactive\b/);
        }
    }
    await select.locator('.select-input').click();
    await expect(dropdown).toBeHidden();
}

/**
 * Wait until the edit form of report `name` has loaded its options. The form fetches them in a chain of
 * requests after a client-side navigation, which `networkidle` does not cover. Leaving the form before
 * the chain ends fails the test: EditUniversalReport's mounted hook does not handle the cancelled request.
 */
async function waitForEditForm(page, name) {
    await expect(reportForm(page).getByPlaceholder('Report name')).toHaveValue(name);
    await expect(multiSelect(page, 0).locator('.select-item')).toHaveCount(5);
    await waitForAppIdle(page);
}

// Universal report create -> list -> view -> edit -> view. The report is personal to the admin and is
// removed through the API after the test; universal_reports is empty in the seed.
test.describe('universal report', () => {
    const prefix = 'E2E universal report ';
    let api;

    async function removeJourneyReports() {
        const { data } = await api.get('report/universal-report');
        for (const report of data.personal.filter(report => report.name.startsWith(prefix))) {
            await api.post('report/universal-report/remove', { id: report.id });
        }
    }

    test.beforeEach(async ({ page }) => {
        api = undefined;
        await page.goto('/about');
        api = await apiClient(page);
        await removeJourneyReports();
    });

    test.afterEach(async () => {
        if (api) {
            await removeJourneyReports();
        }
    });

    test('create, view, edit', async ({ page }) => {
        const name = `${prefix}${Date.now()}`;

        await openFromReportsMenu(page, 'Universal report', '/report/universal');
        const personal = page.locator('.sidebar').filter({ hasText: 'Personal reports' });
        await expect(personal.getByText('No personal reports.')).toBeVisible();

        // Create: a personal report on the seeded project with its name and the time worked per day.
        await page.getByRole('button', { name: 'Create a new report' }).click();
        await expect(page).toHaveURL(/\/report\/universal\/create(\?|$)/);
        await waitForAppIdle(page);
        await reportForm(page).getByPlaceholder('Report name').fill(name);
        await chooseAtSelect(reportForm(page).locator(':scope > .at-select'), 'Project');
        await expect(multiSelect(page, 0).locator('.select-item')).toHaveCount(5);
        await toggleOptions(multiSelect(page, 0), [SEED.project.name]);
        await toggleOptions(multiSelect(page, 1), ['Project name', 'Total time spent per day']);
        await page.getByRole('button', { name: 'Create personal' }).click();

        await expect(page).toHaveURL(/\/report\/universal\/edit\/\d+(\?|$)/);
        const reportId = Number(new URL(page.url()).pathname.split('/').pop());
        await waitForEditForm(page, name);
        const { data: created } = await api.get(`report/universal-report/show?id=${reportId}`);
        expect(created).toMatchObject({
            name,
            type: 'personal',
            base: 'project',
            data_objects: [SEED.project.id],
            fields: { base: ['name'], tasks: [], users: [], calculations: ['total_spent_time_by_day'] },
            charts: [],
        });

        // List: the report is among the personal reports and opens its edit form.
        await page.goto('/report/universal');
        await waitForAppIdle(page);
        await personal.getByRole('link', { name }).click();
        await expect(page).toHaveURL(new RegExp(`/report/universal/edit/${reportId}(\\?|$)`));
        await waitForEditForm(page, name);

        // View: the seeded project has one worker with 10 tasks x 30 intervals x 5 min = 25h on SEED_DATE.
        const view = page.locator('.universal-report__view');
        // One item per data object (Project.vue); its tasks are nested .list__item collapses (TaskInfo.vue).
        const item = view.locator('.list__item:has(> .item-header)');
        await page.getByRole('link', { name: 'Generate' }).click();
        await expect(page).toHaveURL(new RegExp(`/report/universal/view/${reportId}(\\?|$)`));
        await waitForAppIdle(page);
        await expect(view.locator('.control-item.title')).toHaveText(name);
        await expect(view.getByText(`Data for: ${SEED_DATE} - ${SEED_DATE}`)).toBeVisible();
        await expect(item).toHaveCount(1);
        await expect(item.locator('.item-header')).toContainText(SEED.project.name);
        await expect(item.locator('.data-entry', { hasText: 'Worked by day:' })).toContainText(
            new RegExp(`${SEED_DATE}:\\s*25h 0m`),
        );
        // Members are listed with their time only; their names are not among the selected columns yet.
        await expect(item.locator('.base-info .members')).toContainText('25h 0m');
        await expect(item.locator('.base-info .members')).not.toContainText(SEED.user.name);

        // Edit: rename the report and add the members' names, then generate it again.
        await page.goto(`/report/universal/edit/${reportId}`);
        await waitForEditForm(page, name);
        await reportForm(page).getByPlaceholder('Report name').fill(`${name} (edited)`);
        await toggleOptions(multiSelect(page, 1), ['User name']);
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.locator('.at-notification', { hasText: 'Saved successfully' })).toBeVisible();

        const { data: edited } = await api.get(`report/universal-report/show?id=${reportId}`);
        expect(edited).toMatchObject({
            name: `${name} (edited)`,
            data_objects: [SEED.project.id],
            fields: { base: ['name'], users: ['full_name'], calculations: ['total_spent_time_by_day'] },
        });

        await page.getByRole('link', { name: 'Generate' }).click();
        await expect(page).toHaveURL(new RegExp(`/report/universal/view/${reportId}(\\?|$)`));
        await waitForAppIdle(page);
        await expect(view.locator('.control-item.title')).toHaveText(`${name} (edited)`);
        await expect(item.locator('.base-info .members')).toContainText(SEED.user.name);
        await expect(item.locator('.base-info .members')).toContainText('25h 0m');
    });
});

test('screenshots', async ({ page }) => {
    await page.goto('/screenshots');
    await waitForAppIdle(page);
    const thumbs = page.locator('.screenshot img');
    await expect(thumbs.first()).toBeVisible();
    await expect
        .poll(() => thumbs.evaluateAll(images => images.filter(img => img.complete && img.naturalWidth > 0).length))
        .toBeGreaterThan(0);
});

test('calendar', async ({ page }) => {
    await page.goto('/calendar');
    await waitForAppIdle(page);
    await expect(page.locator('.calendar__svg svg').first()).toBeVisible();
});

test('gantt', async ({ page }) => {
    await page.goto(`/gantt/${SEED.project.id}`);
    await waitForAppIdle(page);
    await expect(page.locator('.gantt__chart canvas').first()).toBeVisible();
});

test('dashboard team', async ({ page }) => {
    await page.goto('/dashboard/team');
    await waitForAppIdle(page);
    await expect(page.locator('.team_sidebar').getByText(SEED.user.name)).toBeVisible();
    await expect(page.locator('.team_sidebar').getByText('25h 0m').first()).toBeVisible();
});
