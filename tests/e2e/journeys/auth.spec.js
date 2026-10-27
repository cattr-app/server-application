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

test('login is sent after leaving the page with a captcha and coming back', async ({ page, consoleGuard }) => {
    // The first login attempt is answered with 429, so the captcha appears; Chrome logs the failed resource.
    consoleGuard.expect(/^Failed to load resource: the server responded with a status of 429 \(Too Many Requests\)$/);

    // Stand-in for the Google script (no network on the stand): the same global API, loaded once per tab.
    await page.route('https://www.google.com/recaptcha/api.js*', route =>
        route.fulfill({
            contentType: 'text/javascript',
            body: "window.grecaptcha = { render: () => 0, reset: () => {}, getResponse: () => '' }; window.vueRecaptchaApiLoaded();",
        }),
    );
    let throttled = false;
    await page.route('**/api/auth/login', route => {
        if (throttled) {
            return route.continue();
        }
        throttled = true;
        return route.fulfill({
            status: 429,
            json: { error: { message: 'Too many login attempts' }, info: { site_key: 'e2e-site-key' } },
        });
    });

    const fillAndSubmit = async () => {
        await page.getByPlaceholder('E-Mail').fill('admin@cattr.app');
        await page.getByPlaceholder('Password').fill('password');
        await page.getByRole('button', { name: 'Login' }).click();
    };
    // Client-side navigation; the pushed route object is not returned (it is not serializable).
    const router = to =>
        page.evaluate(path => {
            document.querySelector('#app').__vue__.$router.push(path);
        }, to);

    await page.goto('/auth/login');
    await fillAndSubmit();
    await expect(page.locator('.login__form .recaptcha')).toBeAttached();

    // Leave and come back without a reload: grecaptcha stays loaded, the new form has no captcha.
    await router('/auth/password/reset');
    await expect(page).toHaveURL(/\/auth\/password\/reset/);
    await router('/auth/login');
    await expect(page.locator('.login__form')).toBeVisible();
    await expect(page.locator('.login__form .recaptcha')).toHaveCount(0);

    const loginRequest = page.waitForRequest(request => request.url().endsWith('/api/auth/login'));
    await fillAndSubmit();
    await loginRequest;
    await expect(page).toHaveURL(/\/dashboard/);
});
