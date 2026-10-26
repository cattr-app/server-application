// E2E fixture: a minimal external frontend module (vendor "Test", module "Stub", flag
// MODULE_FRONTEND_TEST_STUB_ENABLED). tests/e2e/module-stub/build.sh copies it into
// resources/frontend/vendor_modules/ for one build only; it is never part of a regular build.
// It deliberately uses no framework API, so it keeps working across the Vue 3 migration.

// Unique marker: the test finds the module chunk by this string in downloaded scripts.
export const E2E_STUB_MARKER = 'cattr-e2e-test-stub-module-chunk';

window.__E2E_TEST_STUB__ = { evaluated: true, initialized: false, marker: E2E_STUB_MARKER };

export const ModuleConfig = {
    routerPrefix: 'test-stub',
    loadOrder: 999,
    moduleName: 'Test_Stub',
};

export function init(context) {
    window.__E2E_TEST_STUB__.initialized = true;
    return context;
}
