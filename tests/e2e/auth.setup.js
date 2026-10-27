// Logs in once through the UI and stores the session (token in localStorage) for the other projects.
import { test } from './support/test.js';
import { loginViaUi } from './support/auth.js';
import { STORAGE_STATE } from './support/constants.js';

test('log in as admin', async ({ page }) => {
    await loginViaUi(page);
    await page.context().storageState({ path: STORAGE_STATE });
});
