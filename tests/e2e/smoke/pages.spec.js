// Smoke: every page of the checklist (migration plan §6) opens and renders its key element.
// The console guard (support/test.js) fails a test on any console error, page error or [Vue warn]
// that is not in tests/e2e/console-allowlist.js; [Vue warn] is printed only by the dev build (`pnpm dev`).
import { test } from '../support/test.js';
import { PAGES } from '../support/pages.js';
import { openPage } from '../support/app.js';

const guest = { cookies: [], origins: [] };

for (const appPage of PAGES) {
    test.describe(() => {
        if (appPage.guest) {
            test.use({ storageState: guest });
        }
        test(`${appPage.id} (${appPage.path})`, async ({ page }) => {
            test.fail(!!appPage.knownBroken, appPage.knownBroken);
            await openPage(page, appPage);
        });
    });
}
