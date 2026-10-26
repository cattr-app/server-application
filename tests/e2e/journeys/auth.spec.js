// Login / logout through the UI. Each test logs in with its own session, so logging out does not
// revoke the shared storageState token of the other tests.
import { test, expect } from '../support/test.js';
import { loginViaUi } from '../support/auth.js';

test.use({ storageState: { cookies: [], origins: [] } });

test('login with a wrong password shows an error and stays on the login page', async ({ page, consoleGuard }) => {
    // The failed POST /api/auth/login is logged by Chrome as a failed resource.
    consoleGuard.expect(/^Failed to load resource: the server responded with a status of 401 \(Unauthorized\)$/);

    await page.goto('/auth/login');
    await page.getByPlaceholder('E-Mail').fill('admin@cattr.app');
    await page.getByPlaceholder('Password').fill('wrong-password');
    await page.getByRole('button', { name: 'Login' }).click();

    await expect(page.locator('.login__error')).toBeVisible();
    await expect(page).toHaveURL(/\/auth\/login/);
});

test('guest is redirected to the login page', async ({ page }) => {
    await page.goto('/tasks');
    await expect(page).toHaveURL(/\/auth\/login/);
    await expect(page.locator('.login__form')).toBeVisible();
});

test('login and logout', async ({ page }) => {
    await loginViaUi(page);
    await expect(page).toHaveURL(/\/dashboard/);
    expect(await page.evaluate(() => localStorage.getItem('access_token'))).toBeTruthy();

    await page.locator('.navbar .at-dropdown__trigger').hover();
    await page.locator('.at-dropdown-menu__item', { hasText: 'Logout' }).click();

    await expect(page).toHaveURL(/\/auth\/login/);
    await expect(page.locator('.login__form')).toBeVisible();
    expect(await page.evaluate(() => localStorage.getItem('access_token'))).toBeNull();

    // The session is gone: protected pages redirect to the login page again.
    await page.goto('/projects');
    await expect(page).toHaveURL(/\/auth\/login/);
});
