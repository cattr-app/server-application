// Manual time: add an interval through "Add time" (/time-intervals/new) for a seeded user and task,
// check it on the server, remove it through the API afterwards.
import { test, expect } from '../support/test.js';
import { apiClient } from '../support/auth.js';
import { waitForAppIdle } from '../support/app.js';
import { chooseAtSelect, chooseVueSelect, field } from '../support/form.js';
import { SEED } from '../support/pages.js';
import { SEED_DATE } from '../support/constants.js';

const intervalsOfDay = api =>
    api.post('time-intervals/list', {
        where: {
            user_id: SEED.user.id,
            start_at: ['between', [`${SEED_DATE} 00:00:00`, `${SEED_DATE} 01:00:00`]],
        },
    });

test('add a time interval manually', async ({ page }) => {
    await page.goto('/dashboard/timeline');
    await waitForAppIdle(page);
    const api = await apiClient(page);
    // The form defaults to 00:00-00:10 of "today" (the frozen date); seeded intervals are 09:30-12:00.
    expect((await intervalsOfDay(api)).data).toEqual([]);

    let created = [];
    try {
        await page.getByRole('link', { name: 'Add time' }).click();
        await expect(page).toHaveURL(/\/time-intervals\/new/);
        await waitForAppIdle(page);

        await chooseAtSelect(field(page, 'User'), SEED.user.name);
        await chooseVueSelect(page, field(page, 'Task'), SEED.task.name.slice(0, 8), SEED.task.name);
        await expect(field(page, 'Start at').locator('input')).toHaveValue(`${SEED_DATE} 00:00`);
        await expect(field(page, 'End at').locator('input')).toHaveValue(`${SEED_DATE} 00:10`);

        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.locator('.at-message, .at-notification').first()).toBeVisible();

        await expect.poll(async () => (created = (await intervalsOfDay(api)).data).length).toBe(1);
        expect(created[0]).toMatchObject({ user_id: SEED.user.id, is_manual: true });
        expect(created[0].start_at).toContain(`${SEED_DATE}`);
    } finally {
        if (!created.length) {
            created = (await intervalsOfDay(api)).data;
        }
        for (const interval of created) {
            await api.post('time-intervals/remove', { id: interval.id });
        }
    }
});
