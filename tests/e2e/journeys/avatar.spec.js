// UserAvatar (navbar): initials when the user has no Gravatar, the Gravatar image when there is one.
import { test, expect } from '../support/test.js';

// 1x1 transparent PNG
const PNG = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
    'base64',
);

test('avatar shows initials when Gravatar answers 404', async ({ page }) => {
    // The harness answers every Gravatar request with 404 (the console message is allowlisted).
    await page.goto('/');
    const avatar = page.locator('.navbar .avatar__photo');
    await expect(avatar).toBeVisible();
    await expect(avatar.locator('span')).toBeVisible();
    await expect(avatar.locator('span')).not.toHaveText('');
    await expect(avatar.locator('img')).toHaveCount(0);
});

test('avatar shows the Gravatar image when it exists', async ({ page }) => {
    // A page route takes precedence over the 404 stub of the context.
    await page.route('https://www.gravatar.com/avatar/**', route =>
        route.fulfill({ status: 200, contentType: 'image/png', body: PNG }),
    );
    await page.goto('/');
    const avatar = page.locator('.navbar .avatar__photo');
    await expect(avatar).toBeVisible();
    await expect(avatar).toHaveCSS('background-image', /gravatar\.com\/avatar\//);
    await expect(avatar.locator('span')).toBeHidden();
});
