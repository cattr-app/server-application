import { expect } from '@playwright/test';
import { ADMIN } from './constants.js';

/** Log in through the login form and wait until the app leaves /auth. */
export async function loginViaUi(page, { email = ADMIN.email, password = ADMIN.password } = {}) {
    await page.goto('/auth/login');
    await page.getByPlaceholder('E-Mail').fill(email);
    await page.getByPlaceholder('Password').fill(password);
    await page.getByRole('button', { name: 'Login' }).click();
    await page.waitForURL(url => !url.pathname.startsWith('/auth'));
    await expect(page.locator('.navbar')).toBeVisible();
}

/** Bearer token of the logged-in page (the app keeps it in localStorage). */
export async function tokenOf(page) {
    return page.evaluate(() => localStorage.getItem('access_token'));
}

/** Logged-in API client for test setup/cleanup: request.post('tasks/create', {...}). */
export async function apiClient(page) {
    const token = await tokenOf(page);
    const headers = { Authorization: `Bearer ${token}`, Accept: 'application/json' };
    const call = async (method, path, data) => {
        const response = await page.request.fetch(`/api/${path}`, { method, headers, data });
        const body = await response.json().catch(() => null);
        if (!response.ok()) {
            throw new Error(`${method} /api/${path} -> ${response.status()}: ${JSON.stringify(body)}`);
        }
        return body;
    };
    return {
        get: path => call('GET', path),
        post: (path, data = {}) => call('POST', path, data),
    };
}
