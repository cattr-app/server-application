// Console messages the e2e console guard (tests/e2e/support/test.js) does not fail on.
// Everything else of type error/warning, every page error and every [Vue warn]/[Vue tip] fails the test.
//
// A rule matches a message when `type` is equal, `text` matches (string: exact text; RegExp: test)
// and, if given, `source` (URL of the script/resource that logged it) and `page` (URL of the page) match.
// Keep rules as narrow as possible and give every rule a comment.

/**
 * Stand rules: properties of the e2e stand, not of the application.
 */
export const STAND_RULES = [
    {
        // Stands A and C (`artisan serve`) have no Reverb, and `artisan serve` does not proxy websockets:
        // laravel-echo/pusher-js fail to connect to ws(s)://<stand>/app/<REVERB_APP_KEY>. Websockets are
        // checked on stand B (docker image with Caddy and Reverb), where E2E_REVERB=1 disables this rule.
        id: 'stand-no-reverb',
        enabled: !process.env.E2E_REVERB,
        type: 'error',
        text: /^WebSocket connection to 'wss?:\/\/127\.0\.0\.1:\d+\/app\/[^'?]+\?protocol=7&client=js&version=[\d.]+&flash=false' failed: /,
    },
    {
        // playwright.config.js blocks service workers (serviceWorkers: 'block'), otherwise the Workbox
        // service worker of the production build would cache responses across tests. Playwright reports it ...
        id: 'stand-sw-blocked',
        type: 'warning',
        text: 'Service Worker registration blocked by Playwright',
    },
    {
        // ... and workbox-window's register() then fails reading `registration.waiting` (only with a blocked
        // registration). The service worker is removed in the migration, and both rules become unused.
        id: 'stand-sw-blocked-workbox',
        type: 'pageerror',
        text: "Cannot read properties of undefined (reading 'waiting')",
    },
];

/**
 * Behaviour of the application that is correct by design but logs to the console.
 */
export const BY_DESIGN = [
    {
        // UserAvatar asks Gravatar with d=404 and falls back to initials when there is no Gravatar.
        // The harness answers every Gravatar request with 404 (no network), so Chrome logs the 404.
        id: 'gravatar-404-fallback',
        type: 'error',
        text: /^Failed to load resource: the server responded with a status of 404 \((Not Found)?\)$/,
        source: /^https:\/\/www\.gravatar\.com\/avatar\/[0-9a-f]{32}\?d=404$/,
    },
    {
        // Leaving a page cancels its pending API requests (store action cancelPendingRequests), and the
        // HTTP interceptor reports every cancellation with console.warn. Timing-dependent.
        id: 'request-canceled-on-navigation',
        type: 'warning',
        text: /^request to [\w/-]+ is canceled$/,
    },
];

/**
 * Known on Vue 2 (main@bfafed73) — must go away in the migration.
 * Every entry is a bug or a deprecation of the current application; remove the entry together with the fix.
 */
export const KNOWN_VUE2 = [
    // --- vue-router 3 deprecations and route table problems (dev build, every page) ---
    {
        // moduleLoader.js registers module routes with router.addRoutes().
        id: 'vue2-router-addroutes-deprecated',
        type: 'warning',
        text: '[vue-router] router.addRoutes() is deprecated and has been removed in Vue Router 4. Use router.addRoute() instead.',
    },
    {
        // Named parent routes with a default child: dashboard and the company CRUD grids.
        id: 'vue2-router-named-route-default-child',
        type: 'warning',
        text: /^\[vue-router\] Named Route '(dashboard|Invitations\.crud\.invitations|Priorities\.crud\.priorities|Statuses\.crud\.statuses|Users\.crud\.users)' has a default child route\. When navigating to this named route \(:to="\{name: '\1'\}"\), the default child route will not be rendered\. Remove the name from this route and use the name of the default child route for named links instead\.$/,
    },
    {
        // The same route names are registered twice (UniversalReport, company CRUD grids).
        id: 'vue2-router-duplicate-named-routes',
        type: 'warning',
        text: /^\[vue-router\] Duplicate named routes definition: \{ name: "(report\.universal\.edit|Invitations\.crud\.invitations|Priorities\.crud\.priorities|Statuses\.crud\.statuses|Users\.crud\.users)", path: "[^"]+" \}$/,
    },
    {
        // /auth/register without an invitation key redirects to the "not-found" route without its param.
        id: 'vue2-router-not-found-missing-param',
        type: 'warning',
        text: '[vue-router] missing param for named route "not-found": Expected "0" to be defined',
        page: /\/auth\/register$/,
    },

    // --- /time-intervals/view/:id: blank page (smoke test marked knownBroken in support/pages.js) ---
    {
        id: 'vue2-time-interval-view-typeerror',
        type: 'error',
        text: /^TypeError: Cannot set properties of undefined \(setting 'from'\)/,
        page: /\/time-intervals\/view\/\d+$/,
    },
    {
        id: 'vue2-time-interval-view-navigation-error',
        type: 'warning',
        text: '[vue-router] uncaught error during route navigation:',
        page: /\/time-intervals\/view\/\d+$/,
    },

    // --- vue-i18n: values passed to $t() that are not translation keys ---
    {
        // Gantt: dimension names of the chart are translated as gantt.dimensions.<column>, keys are missing.
        id: 'vue2-i18n-gantt-dimensions',
        type: 'warning',
        text: /^\[vue-i18n\] (Value of key 'gantt\.dimensions\.(priority_id|status_id|project_phase_id|project_id|total_offset)' is not a string or function !|Cannot translate the value of keypath 'gantt\.dimensions\.(priority_id|status_id|project_phase_id|project_id|total_offset)'\. Use the value of keypath as default\.)$/,
        page: /\/gantt\/\d+$/,
    },
    {
        // User view: the "ID" label is passed to $t() as is.
        id: 'vue2-i18n-user-view-id-label',
        type: 'warning',
        text: /^\[vue-i18n\] (Value of key 'ID' is not a string or function !|Cannot translate the value of keypath 'ID'\. Use the value of keypath as default\.)$/,
        page: /\/company\/users\/view\/\d+$/,
    },
    {
        // Account settings: input placeholders are passed to $t() as is.
        id: 'vue2-i18n-account-placeholders',
        type: 'warning',
        text: /^\[vue-i18n\] (Value of key '(John Snow|user@email\.com|\*{6})' is not a string or function !|Cannot translate the value of keypath '(John Snow|user@email\.com|\*{6})'\. Use the value of keypath as default\.)$/,
        page: /\/settings\/account$/,
    },

    // --- third-party deprecations ---
    {
        // Gantt chart (echarts 5 custom series): api.style() is deprecated.
        id: 'vue2-echarts-api-style-deprecated',
        type: 'warning',
        text: '[ECharts] DEPRECATED: "api.style" has been deprecated. Please write literal style directly instead.',
        page: /\/gantt\/\d+$/,
    },

    // --- [Vue warn] ---
    {
        // About -> storage tab: <at-progress> gets a status value its validator does not accept.
        id: 'vue2-warn-about-storage-progress-status',
        type: 'error',
        text: '[Vue warn]: Invalid prop: custom validator check failed for prop "status".\n\nfound in\n\n---> <AtProgress>\n       <StorageManagementTab> at resources/frontend/core/components/StorageManagementTab.vue\n         <AtTabPane>\n           <AtTabs>\n             <About> at resources/frontend/core/views/About.vue\n               <DefaultLayout> at resources/frontend/core/layouts/DefaultLayout.vue\n                 <App> at resources/frontend/core/App.vue\n                   <Root>',
        page: /\/about$/,
    },
    {
        // The rejected GET /api/report/universal-report (KNOWN_BACKEND) surfaces as an unhandled error
        // of UniversalReport's async mounted hook: the view does not handle the failed request.
        id: 'vue2-warn-universal-report-mounted-unhandled',
        type: 'error',
        text: /^\[Vue warn\]: Error in mounted hook \(Promise\/async\): "AxiosError: Request failed with status code 500"\n\nfound in\n\n---> <UniversalReport> at resources\/frontend\/core\/modules\/UniversalReport\/views\/UniversalReport\.vue\n/,
        page: /\/report\/universal(\/create)?(\?|$)/,
    },
    {
        // Leaving Dashboard -> Team while it loads: ExportDropdown's async created hook does not handle the
        // canceled request (see BY_DESIGN 'request-canceled-on-navigation'). Timing-dependent.
        id: 'vue2-warn-export-dropdown-canceled',
        type: 'error',
        text: /^\[Vue warn\]: Error in created hook \(Promise\/async\): "CanceledError: canceled"\n\nfound in\n\n---> <ExportDropdown> at resources\/frontend\/core\/components\/ExportDropdown\.vue\n/,
    },
    {
        // ... and the same rejection is logged once more by the global error handler.
        id: 'vue2-canceled-error-unhandled',
        type: 'error',
        text: /^CanceledError: canceled\n\s+at Object\.cancel \(/,
    },
];

/**
 * Known backend bugs reproduced by the smoke suite; not related to the frontend migration.
 */
export const KNOWN_BACKEND = [
    {
        // GET /api/report/universal-report answers 500 for an admin: UniversalReportController::index()
        // passes 4-element arrays to where() ("Named parameter $boolean overwrites previous argument").
        id: 'backend-universal-report-index-500',
        type: 'error',
        text: 'Failed to load resource: the server responded with a status of 500 (Internal Server Error)',
        source: /^https?:\/\/127\.0\.0\.1:\d+\/api\/report\/universal-report$/,
    },
    {
        // ... and the Universal report pages log the rejected request.
        id: 'backend-universal-report-index-500-axios',
        type: 'error',
        text: /^AxiosError: Request failed with status code 500\n/,
        page: /\/report\/universal(\/create)?(\?|$)/,
    },
];

export const RULES = [...STAND_RULES, ...BY_DESIGN, ...KNOWN_VUE2, ...KNOWN_BACKEND].filter(
    rule => rule.enabled !== false,
);

const matches = (pattern, value) =>
    pattern === undefined || (pattern instanceof RegExp ? pattern.test(value) : pattern === value);

/**
 * @param {{type: string, text: string, source: string, page: string}} message
 * @returns {object|undefined} the matching rule
 */
export function classifyConsoleMessage(message) {
    return RULES.find(
        rule =>
            rule.type === message.type &&
            matches(rule.text, message.text) &&
            matches(rule.source, message.source) &&
            matches(rule.page, message.page),
    );
}
