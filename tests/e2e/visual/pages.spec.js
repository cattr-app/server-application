// Visual regression baseline of the checklist pages (PAGES with `visual: true`), for the
// visual-desktop (1440x900) and visual-mobile (390x844) projects of playwright.config.js.
// Baselines live in tests/e2e/__screenshots__/<project>/ and are taken only inside the Playwright image
// (see tests/e2e/README.md). The clock, language, timezone and seed are fixed (support/test.js, support/seed.php).
import { test, expect } from '../support/test.js';
import { PAGES } from '../support/pages.js';
import { openPage } from '../support/app.js';

// Masked everywhere: content that is random by nature or animated by JavaScript.
const GLOBAL_MASKS = [
    '.avatar__photo', // avatars (initials/colours or Gravatar); class set by UserAvatar.vue
    '.vue-avatar--wrapper',
    '.avatar__online-status', // online state depends on the server's real clock
    '.screenshot__image', // seeded screenshots are random colour fills
    '.screenshots .screenshot img',
    'canvas', // chart.js / echarts: animated, rendered by JavaScript
    '.about__version', // build/version info
];

// Pauses CSS animations/transitions and hides the caret and scrollbars, which differ between runs.
const STABILIZE_CSS = `
    *, *::before, *::after { animation: none !important; transition: none !important; caret-color: transparent !important; }
    ::-webkit-scrollbar { display: none !important; }
`;

const guest = { cookies: [], origins: [] };

for (const appPage of PAGES.filter(p => p.visual)) {
    test.describe(() => {
        if (appPage.guest) {
            test.use({ storageState: guest });
        }
        test(appPage.id, async ({ page }) => {
            await openPage(page, appPage);
            await page.addStyleTag({ content: STABILIZE_CSS });
            // Close dropdowns/tooltips opened by focus and let web fonts/images settle.
            await page.mouse.move(0, 0);
            await page.evaluate(() => document.fonts.ready);
            await expect(page).toHaveScreenshot(`${appPage.id}.png`, {
                fullPage: true,
                mask: [...GLOBAL_MASKS, ...(appPage.mask || [])].map(selector => page.locator(selector)),
            });
        });
    });
}
