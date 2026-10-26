// Switch the account language to Russian and back (Settings -> Account -> User Language).
// The admin's user_language is restored through the API whatever happens.
import { test, expect } from '../support/test.js';
import { apiClient } from '../support/auth.js';
import { waitForAppIdle } from '../support/app.js';
import { chooseAtSelect } from '../support/form.js';

const languageRow = page =>
    page.locator('.settings__content .row, .settings__content .data-entry').filter({ hasText: /User Language|Язык/ });

test('switch the interface language', async ({ page }) => {
    await page.goto('/settings/account');
    await waitForAppIdle(page);
    const api = await apiClient(page);
    const { data: me } = await api.get('auth/me');
    expect(me.user_language).toBe('en');

    try {
        await chooseAtSelect(languageRow(page), 'Русский');
        await page.getByRole('button', { name: 'Save' }).click();
        await expect(page.locator('.navbar')).toContainText('Задачи');
        await expect(page.locator('.page-title').first()).toHaveText('Настройки');

        await page.reload();
        await expect(page.locator('.navbar')).toContainText('Задачи');

        await chooseAtSelect(languageRow(page), 'English');
        await page.getByRole('button', { name: 'Сохранить' }).click();
        await expect(page.locator('.navbar')).toContainText('Tasks');
        await expect(page.locator('.page-title').first()).toHaveText('Settings');
    } finally {
        const { data: current } = await api.get('auth/me');
        if (current.user_language !== 'en') {
            // screenshots_state is sent too: users/edit without it answers 500 on main@bfafed73.
            await api.post('users/edit', {
                id: current.id,
                user_language: 'en',
                screenshots_state: current.screenshots_state,
            });
        }
    }
});
