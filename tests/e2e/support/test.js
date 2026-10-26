// Base test of all e2e suites: deterministic browser environment and a console guard.
import fs from 'node:fs';
import { test as base, expect } from '@playwright/test';
import { BASE_URL, FROZEN_NOW } from './constants.js';
import { classifyConsoleMessage } from '../console-allowlist.js';

const STAND_ORIGIN = new URL(BASE_URL).origin;

/**
 * External requests never reach the network, so results do not depend on it:
 * - gravatar.com: answered with 404, as for an address without a Gravatar (the avatar falls back to initials);
 * - fonts.googleapis.com / fonts.gstatic.com: empty responses (system fallback font of the Playwright image);
 * - anything else: aborted.
 */
async function isolateNetwork(context) {
    await context.route(
        url => url.origin !== STAND_ORIGIN && url.protocol.startsWith('http'),
        route => {
            const { hostname } = new URL(route.request().url());
            if (hostname.endsWith('gravatar.com')) {
                return route.fulfill({ status: 404, body: '' });
            }
            if (hostname === 'fonts.googleapis.com') {
                return route.fulfill({ status: 200, contentType: 'text/css', body: '' });
            }
            if (hostname === 'fonts.gstatic.com') {
                return route.fulfill({ status: 404, body: '' });
            }
            return route.abort('blockedbyclient');
        },
    );
}

export const test = base.extend({
    /**
     * Console messages and page errors of the test, classified by tests/e2e/console-allowlist.js.
     * `consoleGuard.expect(pattern)` allows messages a test provokes on purpose (e.g. a failed login).
     * The guard fails the test on any unexpected error, warning or [Vue warn].
     */
    consoleGuard: [
        async ({ page }, use, testInfo) => {
            const unexpected = [];
            const allowed = [];
            const expected = [];
            const record = entry => {
                if (expected.some(re => re.test(entry.text))) {
                    allowed.push({ ...entry, rule: 'expected-by-test' });
                    return;
                }
                const rule = classifyConsoleMessage(entry);
                if (rule) {
                    allowed.push({ ...entry, rule: rule.id });
                } else if (entry.type === 'error' || entry.type === 'warning' || entry.type === 'pageerror') {
                    unexpected.push(entry);
                } else if (/\[Vue (warn|tip)\]/.test(entry.text)) {
                    unexpected.push(entry);
                }
            };
            page.on('console', msg =>
                record({
                    type: msg.type(),
                    text: msg.text(),
                    source: msg.location().url || '',
                    page: page.url(),
                }),
            );
            page.on('pageerror', error =>
                record({ type: 'pageerror', text: String(error.message), source: '', page: page.url() }),
            );

            await use({
                expect: pattern => expected.push(pattern),
                unexpected,
                allowed,
            });

            if (allowed.length) {
                await testInfo.attach('console-allowed.json', {
                    body: JSON.stringify(allowed, null, 2),
                    contentType: 'application/json',
                });
            }
            if (process.env.E2E_CONSOLE_COLLECT) {
                // Inventory mode (see README): record unexpected messages instead of failing.
                fs.mkdirSync('test-results', { recursive: true });
                for (const entry of unexpected) {
                    fs.appendFileSync(
                        'test-results/console-collected.jsonl',
                        `${JSON.stringify({ test: testInfo.titlePath.join(' > '), ...entry })}\n`,
                    );
                }
            } else if (testInfo.status === testInfo.expectedStatus) {
                expect(
                    unexpected.map(e => `[${e.type}] ${e.text}${e.source ? ` (${e.source})` : ''} @ ${e.page}`),
                    'console errors, warnings and [Vue warn] (see tests/e2e/console-allowlist.js)',
                ).toEqual([]);
            }
        },
        { auto: true },
    ],

    // Fixed language, clock and network for every page of the context.
    context: async ({ context }, use) => {
        await context.addCookies([{ name: 'lang', value: 'en', url: BASE_URL }]);
        await isolateNetwork(context);
        await use(context);
    },

    page: async ({ page }, use) => {
        // install() (not setFixedTime): time starts at FROZEN_NOW and keeps flowing, so lodash
        // throttle/debounce and timers behave normally.
        await page.clock.install({ time: new Date(FROZEN_NOW) });
        await use(page);
    },
});

export { expect };
