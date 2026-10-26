// Create and edit a task through the CRUD forms; the task is removed through the API afterwards.
import { test, expect } from '../support/test.js';
import { apiClient } from '../support/auth.js';
import { waitForAppIdle } from '../support/app.js';
import { chooseAtSelect, field } from '../support/form.js';
import { SEED } from '../support/pages.js';

test('create and edit a task', async ({ page }) => {
    const name = `E2E task ${Date.now()}`;
    let taskId;

    await page.goto('/tasks/new');
    await waitForAppIdle(page);
    try {
        await chooseAtSelect(field(page, 'Project'), SEED.project.name);
        await field(page, 'Name').locator('input').fill(name);
        await field(page, 'Description').locator('.ql-editor').fill('Created by the e2e suite.');
        await chooseAtSelect(field(page, 'Priority'), 'High');
        await chooseAtSelect(field(page, 'Status'), 'Open');
        await page.getByRole('button', { name: 'Save' }).click();

        await expect(page).toHaveURL(/\/tasks\/view\/\d+/);
        taskId = Number(new URL(page.url()).pathname.split('/').pop());
        await expect(page.locator('.crud__item-view .control-item.title')).toHaveText(name);

        await page.goto(`/tasks/edit/${taskId}`);
        await waitForAppIdle(page);
        await expect(field(page, 'Name').locator('input')).toHaveValue(name);
        await field(page, 'Name').locator('input').fill(`${name} (edited)`);
        await chooseAtSelect(field(page, 'Status'), 'Closed');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.locator('.at-message, .at-notification').first()).toBeVisible();

        const api = await apiClient(page);
        const { data } = await api.post('tasks/show', { id: taskId });
        expect(data.task_name).toBe(`${name} (edited)`);
        expect(data.status_id).toBe(2);
        expect(data.priority_id).toBe(3);
        expect(data.project_id).toBe(SEED.project.id);
    } finally {
        const api = await apiClient(page);
        if (!taskId) {
            const { data } = await api.post('tasks/list', { where: { task_name: ['like', `${name}%`] } });
            taskId = data?.[0]?.id;
        }
        if (taskId) {
            await api.post('tasks/remove', { id: taskId });
        }
    }
});
