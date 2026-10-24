# Module activation flags

External modules are enabled only through environment variables:

```
MODULE_BACKEND_<FLAG_NAME>_ENABLED=true     # backend module (module.json "name")
MODULE_FRONTEND_<FLAG_NAME>_ENABLED=true    # frontend module (ModuleConfig.moduleName)
```

Only the exact lowercase string `true` enables a module. Backend and frontend modules are
installed and enabled independently, so each side has its own flag.

## How `<FLAG_NAME>` is derived

The same algorithm is implemented in PHP (`App\Services\ModuleActivatorService::flagName()`)
and in JavaScript (`resources/frontend/core/utils/moduleFlagName.js`); both are tested against
`tests/fixtures/module-flag-names.json`.

1. Insert `_` between a lowercase letter or digit and the uppercase letter that follows it
   (`CattrRedmine` -> `Cattr_Redmine`, `V2Sync` -> `V2_Sync`).
2. Replace every run of characters other than ASCII letters and digits with a single `_`.
3. Convert to uppercase.
4. Collapse repeated `_` and trim `_` from both ends.

Digits do not start a new word (`Module2` -> `MODULE2`), and runs of capitals stay together
(`HTTPServer` -> `HTTPSERVER`).

| Identifier | Flag |
|---|---|
| `CattrRedmineIntegration` (backend `module.json` name) | `MODULE_BACKEND_CATTR_REDMINE_INTEGRATION_ENABLED` |
| `AmazingCat_RedmineIntegration` (frontend `moduleName`) | `MODULE_FRONTEND_AMAZING_CAT_REDMINE_INTEGRATION_ENABLED` |
| `CattrDemo` | `MODULE_BACKEND_CATTR_DEMO_ENABLED` |
| `AmazingCat_DemoModule` | `MODULE_FRONTEND_AMAZING_CAT_DEMO_MODULE_ENABLED` |

## Frontend package modules

For a package module the flag is checked before its chunk is downloaded, so the frontend derives
`<FLAG_NAME>` from the module's key in `resources/frontend/etc/modules.config.json`, not from the
`ModuleConfig.moduleName` inside the package. Register packages under a key equal to their
`moduleName` (for example `"AmazingCat_RedmineIntegration": { "type": "package", "ref": "@amazingcat/cattr-redmine-integration" }`),
otherwise the flag name in the documentation of the module will not match.
