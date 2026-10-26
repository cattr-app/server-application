// Every report opens from the navigation and shows the seeded data (09:30-12:00 UTC on SEED_DATE,
// 5 users x 10 tasks x 30 intervals of 5 minutes) with its chart/graphics element.
import { test, expect } from '../support/test.js';
import { waitForAppIdle } from '../support/app.js';
import { SEED } from '../support/pages.js';

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
    await expect(page).toHaveURL(new RegExp(path.replace(/\//g, '\\/')));
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

test('universal report', async ({ page }) => {
    // Known backend bug (see console-allowlist.js, KNOWN_BACKEND): the report list answers 500 for an admin.
    await openFromReportsMenu(page, 'Universal report', '/report/universal');
    await expect(page.locator('.page-title', { hasText: 'Universal report' })).toBeVisible();
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
