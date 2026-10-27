// Create and edit a task through the CRUD forms. Works in its own project (created and removed through
// the API): the form preselects all users, and saving a task adds its assignees to the project's members
// (Task::addAssigneesToProject), which removing the task does not undo.
import { test, expect } from '../support/test.js';
import { apiClient } from '../support/auth.js';
import { waitForAppIdle } from '../support/app.js';
import { chooseAtSelect, field } from '../support/form.js';

test('create and edit a task', async ({ page }) => {
    const suffix = Date.now();
    const name = `E2E task ${suffix}`;
    let taskId;

    await page.goto('/tasks');
    const api = await apiClient(page);
    const { data: project } = await api.post('projects/create', {
        name: `E2E tasks ${suffix}`,
        description: 'Created by the e2e suite.',
        screenshots_state: 1,
    });

    try {
        await page.goto('/tasks/new');
        await waitForAppIdle(page);
        await chooseAtSelect(field(page, 'Project'), project.name);
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

        const { data } = await api.post('tasks/show', { id: taskId });
        expect(data.task_name).toBe(`${name} (edited)`);
        expect(data.status_id).toBe(2);
        expect(data.priority_id).toBe(3);
        expect(data.project_id).toBe(project.id);
    } finally {
        if (!taskId) {
            const { data } = await api.post('tasks/list', { where: { task_name: ['like', `${name}%`] } });
            taskId = data?.[0]?.id;
        }
        if (taskId) {
            await api.post('tasks/remove', { id: taskId });
        }
        await api.post('projects/remove', { id: project.id });
    }
});
