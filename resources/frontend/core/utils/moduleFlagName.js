/**
 * Derive the <FLAG_NAME> part of MODULE_{BACKEND,FRONTEND}_<FLAG_NAME>_ENABLED
 * from a module identifier. Must stay in sync with
 * App\Services\ModuleActivatorService::flagName() (see docs/dev/module-activation-flags.md);
 * both are tested against tests/fixtures/module-flag-names.json.
 *
 * @param {string} name
 * @returns {string}
 */
export function moduleFlagName(name) {
    return (
        String(name)
            // 1. Split a lowercase letter or digit from the following uppercase letter.
            .replace(/([a-z0-9])([A-Z])/g, '$1_$2')
            // 2. Replace every run of characters other than ASCII letters and digits with a single underscore.
            .replace(/[^A-Za-z0-9]+/g, '_')
            // 3. Uppercase.
            .toUpperCase()
            // 4. Collapse repeated underscores and trim them from both ends.
            .replace(/_+/g, '_')
            .replace(/^_+|_+$/g, '')
    );
}

export default moduleFlagName;
