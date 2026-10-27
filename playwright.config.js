// Playwright e2e harness (smoke, journeys, visual); see tests/e2e/README.md.
// Runs only inside mcr.microsoft.com/playwright:v<@playwright/test version>-noble: browsers are not
// installed on developer hosts, and visual baselines are valid only for that image.
import { defineConfig, devices } from '@playwright/test';
import { BASE_URL, LOCALE, STORAGE_STATE, TIMEZONE } from './tests/e2e/support/constants.js';

if (!process.env.PLAYWRIGHT_BROWSERS_PATH && !process.env.E2E_ALLOW_HOST) {
    throw new Error(
        'Run the e2e suites inside mcr.microsoft.com/playwright:v<@playwright/test version>-noble ' +
            '(see tests/e2e/README.md); set E2E_ALLOW_HOST=1 to bypass this check at your own risk.',
    );
}

const chromium = {
    ...devices['Desktop Chrome'],
    baseURL: BASE_URL,
    locale: LOCALE,
    timezoneId: TIMEZONE,
    viewport: { width: 1440, height: 900 },
    // The production build registers a Workbox service worker; it would cache responses across
    // tests and hide requests from page.route()/network assertions.
    serviceWorkers: 'block',
};
const loggedIn = { ...chromium, storageState: STORAGE_STATE };

export default defineConfig({
    testDir: 'tests/e2e',
    outputDir: 'test-results',
    snapshotPathTemplate: '{testDir}/__screenshots__/{projectName}/{arg}{ext}',
    // Projects share one seeded database: run everything in one worker, projects in the order below.
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: 0,
    timeout: 60_000,
    expect: {
        timeout: 15_000,
        toHaveScreenshot: {
            animations: 'disabled',
            caret: 'hide',
            scale: 'css',
            // Anti-aliasing noise only; real regressions are larger. Diffs are reviewed by hand.
            maxDiffPixelRatio: 0.002,
            threshold: 0.2,
        },
    },
    reporter: process.env.CI
        ? [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }], ['github']]
        : [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }]],
    use: {
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        actionTimeout: 15_000,
        navigationTimeout: 30_000,
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.js/, use: chromium },
        { name: 'smoke', testMatch: /smoke\/.*\.spec\.js/, dependencies: ['setup'], use: loggedIn },
        {
            name: 'visual-desktop',
            testMatch: /visual\/.*\.spec\.js/,
            dependencies: ['setup'],
            use: { ...loggedIn, viewport: { width: 1440, height: 900 } },
        },
        {
            name: 'visual-mobile',
            testMatch: /visual\/.*\.spec\.js/,
            dependencies: ['setup'],
            use: { ...loggedIn, viewport: { width: 390, height: 844 }, isMobile: false, hasTouch: false },
        },
        // Journeys mutate data (and restore it); they run after the read-only suites.
        {
            name: 'journeys',
            testMatch: /journeys\/.*\.spec\.js/,
            dependencies: ['setup'],
            use: loggedIn,
        },
        // Lazy chunk of an external frontend module; needs a special build, run by tests/e2e/module-stub.sh.
        ...(process.env.E2E_MODULE_STUB
            ? [{ name: 'module-stub', testMatch: /module-stub\/.*\.spec\.js/, dependencies: ['setup'], use: loggedIn }]
            : []),
    ],
});
