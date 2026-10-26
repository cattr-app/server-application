// Lazy loading of external frontend modules: the chunk of the fixture module Test/Stub is downloaded
// only when the backend runs with MODULE_FRONTEND_TEST_STUB_ENABLED=true.
// Needs a build made by tests/e2e/module-stub/build.sh and E2E_MODULE_STUB=enabled|disabled describing
// the backend flag of the stand (see tests/e2e/README.md, "Module flag").
import { test, expect } from '../support/test.js';
import { waitForAppIdle } from '../support/app.js';

const MARKER = 'cattr-e2e-test-stub-module-chunk';
const FLAG = 'TEST_STUB';
const expected = process.env.E2E_MODULE_STUB;

test(`module chunk is ${expected === 'enabled' ? '' : 'not '}requested (flag ${expected})`, async ({ page }) => {
    expect(['enabled', 'disabled'], 'E2E_MODULE_STUB must be "enabled" or "disabled"').toContain(expected);

    const scripts = [];
    page.on('response', response => {
        if (response.request().resourceType() === 'script') {
            scripts.push(response);
        }
    });

    await page.goto('/dashboard');
    await expect(page.locator('.navbar')).toBeVisible();
    await waitForAppIdle(page);

    // The stand really runs with the expected flag (PHP injects it into the page).
    const flags = await page.evaluate(() => window.__CATTR_FRONTEND_MODULES__);
    expect(flags?.[FLAG] === true).toBe(expected === 'enabled');

    const bodies = await Promise.all(scripts.map(r => r.text().catch(() => '')));
    const stubChunks = scripts.filter((_, i) => bodies[i].includes(MARKER)).map(r => r.url());
    const mainBundleHasStub = bodies.some(
        (body, i) => body.includes(MARKER) && /\/app\.js(\?|$)/.test(new URL(scripts[i].url()).pathname),
    );
    expect(mainBundleHasStub, 'the stub must be a separate chunk, not part of app.js').toBe(false);

    const state = await page.evaluate(() => window.__E2E_TEST_STUB__ ?? null);
    if (expected === 'enabled') {
        expect(stubChunks).toHaveLength(1);
        expect(state).toEqual({ evaluated: true, initialized: true, marker: MARKER });
    } else {
        expect(stubChunks).toEqual([]);
        expect(state).toBeNull();
    }
});
