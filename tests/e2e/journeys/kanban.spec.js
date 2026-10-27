// Project kanban (vue-kanban/dragula): drag a card to another column, the task status changes on the server.
// Works on its own project (created and removed through the API), seeded data stays untouched.
import { test, expect } from '../support/test.js';
import { apiClient } from '../support/auth.js';
import { waitForAppIdle } from '../support/app.js';

/** Drag with real mouse events in small steps (dragula reacts to mousedown/mousemove/mouseup). */
async function dragTo(page, source, target) {
    const from = await source.boundingBox();
    const to = await target.boundingBox();
    await page.mouse.move(from.x + from.width / 2, from.y + 20);
    await page.mouse.down();
    await page.mouse.move(from.x + from.width / 2 + 10, from.y + 30, { steps: 5 });
    await page.mouse.move(to.x + to.width / 2, to.y + 40, { steps: 20 });
    await page.mouse.up();
}

test('drag a task card to another status column', async ({ page }) => {
    const suffix = Date.now();
    await page.goto('/projects');
    const api = await apiClient(page);

    const { data: project } = await api.post('projects/create', {
        name: `E2E kanban ${suffix}`,
        description: 'Created by the e2e suite.',
        screenshots_state: 1,
    });
    let taskId;
    try {
        const { data: task } = await api.post('tasks/create', {
            project_id: project.id,
            task_name: `E2E card ${suffix}`,
            description: 'Drag me',
            status_id: 1,
            priority_id: 2,
            users: [],
        });
        taskId = task.id;

        await page.goto(`/projects/${project.id}/tasks/kanban`);
        const card = page.locator(`.drag-item[data-block-id="${taskId}"]`);
        await expect(page.locator('.drag-inner-list[data-status="Open"]').locator(card)).toBeVisible();
        await waitForAppIdle(page);

        const statusUpdated = page.waitForResponse(
            r => r.url().endsWith('/api/tasks/edit') && r.request().method() === 'POST',
        );
        await dragTo(page, card, page.locator('.drag-inner-list[data-status="Closed"]'));
        expect((await statusUpdated).ok()).toBe(true);

        const { data: saved } = await api.post('tasks/show', { id: taskId });
        expect(saved.status_id).toBe(2);

        await page.reload();
        await expect(page.locator('.drag-inner-list[data-status="Closed"]').locator(card)).toBeVisible();
        await expect(page.locator('.drag-inner-list[data-status="Open"]').locator(card)).toHaveCount(0);
    } finally {
        if (taskId) {
            await api.post('tasks/remove', { id: taskId });
        }
        if (project?.id) {
            await api.post('projects/remove', { id: project.id });
        }
    }
});
