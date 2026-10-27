// Leaving a page while its API requests are pending cancels them (router guard -> store action
// cancelPendingRequests). Components must not leave such a request as an unhandled rejection: the console
// guard of support/test.js fails the test on the resulting "Error in ... hook" / CanceledError messages.
// Each test holds one request until the page has been left, so the cancellation is deterministic.
import { test, expect } from '../support/test.js';
import { waitForAppIdle } from '../support/app.js';

/** Holds requests matching `pattern` until release(); `requested` resolves when the first one arrives. */
async function holdRequest(page, pattern) {
    let release;
    const released = new Promise(resolve => (release = resolve));
    let markRequested;
    const requested = new Promise(resolve => (markRequested = resolve));
    await page.route(pattern, async route => {
        markRequested();
        await released;
        // The browser has already canceled the request; continuing it may throw.
        await route.continue().catch(() => {});
    });
    return { requested, release };
}

/** Client-side navigation (a full page load would not cancel anything). */
async function navigateInApp(page, path) {
    // The pushed route object is not returned: it is not serializable.
    await page.evaluate(to => {
        document.querySelector('#app').__vue__.$router.push(to);
    }, path);
    await expect(page).toHaveURL(new RegExp(`${path.replace(/\//g, '\\/')}$`));
}

test('leaving Dashboard -> Team before the export types load', async ({ page }) => {
    const held = await holdRequest(page, '**/api/about/reports');
    const aborted = page.waitForEvent('requestfailed', request => request.url().includes('/api/about/reports'));

    await page.goto('/dashboard/team');
    await held.requested;
    await navigateInApp(page, '/about');
    await aborted;
    held.release();
    await waitForAppIdle(page);
});

test('leaving the universal report form before its data objects load', async ({ page }) => {
    // The report itself is not needed: answer `show` with a minimal report and hold the next request.
    await page.route('**/api/report/universal-report/show?id=424242', route =>
        route.fulfill({
            json: {
                data: {
                    id: 424242,
                    name: 'Pending report',
                    base: 'project',
                    fields: {},
                    data_objects: [],
                    charts: [],
                    type: 'personal',
                },
            },
        }),
    );
    const held = await holdRequest(page, '**/api/report/universal-report/data-objects-and-fields?*');
    const aborted = page.waitForEvent('requestfailed', request =>
        request.url().includes('/api/report/universal-report/data-objects-and-fields'),
    );

    await page.goto('/report/universal/edit/424242');
    await held.requested;
    await navigateInApp(page, '/about');
    await aborted;
    held.release();
    await waitForAppIdle(page);
});
