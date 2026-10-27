import { expect } from '@playwright/test';

// Loading indicators of the app: Preloader, vue-loading-skeleton, AT-UI loading bar.
const LOADING = '.loader, .pu-skeleton, .at-loading-bar';

/** Wait until the page has no pending requests and no visible loading indicators. */
export async function waitForAppIdle(page) {
    await page.waitForLoadState('networkidle');
    await expect(page.locator(LOADING).locator('visible=true')).toHaveCount(0);
    // One more quiet period: some views fetch again after the first render.
    await page.waitForLoadState('networkidle');
}

/** Open a page of the checklist and wait for its key element. */
export async function openPage(page, appPage) {
    await page.goto(appPage.path);
    await expect(appPage.ready(page)).toBeVisible();
    await waitForAppIdle(page);
}
