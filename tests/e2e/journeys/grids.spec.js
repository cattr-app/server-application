// Grids (resources/frontend/core/views/Crud/GridView.vue): search filter, pagination (incl. deep link, reload, history), sorting.
import { test, expect } from '../support/test.js';
import { waitForAppIdle } from '../support/app.js';

const rows = page => page.locator('.crud__table .at-table__tbody tr');
const firstColumn = page => rows(page).locator('td:first-child');

test('projects grid: search filter', async ({ page }) => {
    await page.goto('/projects');
    await expect(rows(page)).toHaveCount(5);

    await page.getByPlaceholder('Enter project name').fill('Ut animi');
    await expect(rows(page)).toHaveCount(1);
    await expect(rows(page).first()).toContainText('Ut animi assumenda.');

    await page.getByPlaceholder('Enter project name').fill('no such project');
    await expect(page.locator('.crud__table .at-table__cell--nodata')).toBeVisible();

    await page.getByPlaceholder('Enter project name').fill('');
    await expect(rows(page)).toHaveCount(5);
});

test('tasks grid: search filter and pagination', async ({ page }) => {
    await page.goto('/tasks');
    await expect(page.locator('.crud__title, .page-title').first()).toBeVisible();
    await expect(rows(page)).toHaveCount(15);
    await expect(page.locator('.at-pagination__item')).toHaveText(['1', '2', '3', '4']);
    const firstPage = await firstColumn(page).allTextContents();

    await page.locator('.at-pagination__item', { hasText: '2' }).click();
    await expect(page.locator('.at-pagination__item--active')).toHaveText('2');
    await expect(page).toHaveURL(/page=2/);
    await expect(firstColumn(page).first()).not.toHaveText(firstPage[0]);
    await waitForAppIdle(page);
    const secondPage = await firstColumn(page).allTextContents();
    expect(secondPage).toHaveLength(15);
    expect(secondPage.filter(name => firstPage.includes(name))).toEqual([]);

    await page.locator('.at-pagination__item', { hasText: '4' }).click();
    await expect(rows(page)).toHaveCount(5); // 50 tasks: 15 + 15 + 15 + 5

    await page.getByPlaceholder('Enter task name').fill('Odio dolores');
    await expect(rows(page)).toHaveCount(1);
    await expect(rows(page).first()).toContainText('Odio dolores aliquam ut.');
});

const taskRequest = page =>
    page.waitForResponse(response => {
        const url = new URL(response.url());
        return url.pathname.endsWith('/tasks/list') && response.request().method() === 'POST';
    });

// The select filters of the tasks grid (projects, users, statuses) load asynchronously and emit their
// restored selection on init; that must not override the page requested in the URL.
async function expectTasksPage(page, number, request) {
    const response = await request;
    expect(response.request().postDataJSON().page).toBe(number);
    await expect(page.locator('.at-pagination__item--active')).toHaveText(String(number));
    await expect(page).toHaveURL(new RegExp(`page=${number}`));
    await waitForAppIdle(page);
}

test('tasks grid: deep link, reload and history keep the page', async ({ page }) => {
    let request = taskRequest(page);
    await page.goto('/tasks?page=2');
    await expectTasksPage(page, 2, request);
    const secondPage = await firstColumn(page).allTextContents();
    expect(secondPage).toHaveLength(15);

    request = taskRequest(page);
    await page.reload();
    await expectTasksPage(page, 2, request);
    expect(await firstColumn(page).allTextContents()).toEqual(secondPage);

    request = taskRequest(page);
    await page.locator('.at-pagination__item', { hasText: '3' }).click();
    await expectTasksPage(page, 3, request);
    const thirdPage = await firstColumn(page).allTextContents();

    request = taskRequest(page);
    await page.goBack();
    await expectTasksPage(page, 2, request);
    expect(await firstColumn(page).allTextContents()).toEqual(secondPage);

    request = taskRequest(page);
    await page.goForward();
    await expectTasksPage(page, 3, request);
    expect(await firstColumn(page).allTextContents()).toEqual(thirdPage);

    // Leave the grid for a task and come back with the browser Back button: the grid is remounted.
    // At the 1440px viewport row actions are inline icon buttons (the toggle exists only <= 1200px).
    await rows(page)
        .first()
        .locator('.action-button', { has: page.locator('.icon-eye') })
        .click();
    await expect(page).toHaveURL(/\/tasks\/view\/\d+/);
    await waitForAppIdle(page);
    request = taskRequest(page);
    await page.goBack();
    await expectTasksPage(page, 3, request);
    expect(await firstColumn(page).allTextContents()).toEqual(thirdPage);
});

test('users grid: sorting by column', async ({ page }) => {
    await page.goto('/company/users');
    await expect(rows(page)).toHaveCount(6);
    const header = page.locator('.crud__table th.at-table__column', { hasText: 'Name' });
    const names = () => firstColumn(page).allTextContents();

    const initial = await names();
    await header.click();
    await waitForAppIdle(page);
    const afterFirstClick = await names();
    await header.click();
    await waitForAppIdle(page);
    const afterSecondClick = await names();

    const asc = [...initial].sort((a, b) => a.localeCompare(b));
    // One of the two clicks sorts ascending, the other one descending.
    expect([afterFirstClick, afterSecondClick]).toContainEqual(asc);
    expect([afterFirstClick, afterSecondClick]).toContainEqual([...asc].reverse());
});
