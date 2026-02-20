# AGENTS

## Purpose
- This file guides agentic work in this repo.
- Prefer existing patterns; avoid reformatting unrelated code.
- Update this file if new tooling or rules appear.

## Repo Overview
- Electron main process lives in `app/src` (CommonJS modules).
- Vue 2 renderer lives in `app/renderer` (ES modules, Vue SFCs).
- Renderer build uses Laravel Mix + Webpack into `build/`.
- Packaging uses electron-builder, outputs to `target/`.
- IPC is handled by `@amazingcat/electron-ipc-router`.
- Local data uses Sequelize models + migrations in `app/src/models` and `app/src/migrations`.

## Project Structure
- `app/src/app.js` Electron main entry.
- `app/src/base` core services (config, auth, OS integration).
- `app/src/controller` domain logic for projects/tasks/time.
- `app/src/routes` IPC route handlers.
- `app/src/utils` logging, errors, time helpers.
- `app/renderer/js/components` Vue UI components.
- `app/renderer/js/storage` Vuex store modules.
- `app/renderer/js/router` Vue Router config.
- `app/renderer/scss` shared styling and Element UI theme overrides.

## Setup
- Node version: 14.19.0 (`.nvmrc`).
- Yarn version: 3.2.1 (`packageManager`).
- Yarn uses `node-modules` linker (`.yarnrc.yml`).
- Private registry for scope `@cattr` configured in `.yarnrc.yml`.
- macOS/Linux build deps in `README.md` (Xcode, build-essential, etc.).
- Supported OS build targets are listed in `README.md`.
- `.env` and `.env.test` are ignored; never commit secrets.

## Install & Lint
- Install deps: `yarn`
- Lint JS/Vue: `yarn lint` (eslint)

## Development Commands
- Build renderer (dev): `yarn build-development`
- Build renderer (dev watch): `yarn build-watch`
- Run Electron in dev mode: `yarn dev` (expects `build/`).
- Run without screenshots: `yarn dev-no-scr`
- Run without screenshots and devtools: `yarn dev-no-scr-no-devtools`
- Run with mock screenshots only: `yarn no-scr`
- Enable remote Vue devtools: `yarn dev-vue`
- Windows dev entry: `yarn dev-win`
- Production-like start: `yarn start` (electron `app/src/app.js`)

## Build & Release Commands
- Build renderer (prod): `yarn build-production`
- Build renderer + Sentry release: `yarn build-release` (uses `.sentry.json`)
- Clean dev artifacts: `yarn clean-development`
- Set app version before packaging (per README).
- Run `npm config set git-tag-version false`.
- Run `npm version vX.Y.Z`.

## Packaging Commands
- macOS DMG (signed/notarized): `yarn package-mac`
- macOS DMG unsigned: `yarn package-mac-unsigned`
- Linux artifacts: `yarn package-linux`
- Linux dev package: `yarn package-dev-linux`
- Windows artifacts: `yarn package-windows`
- Windows dev package: `yarn package-dev-windows`

## Tests
- Run all tests: `php artisan test`
- Run a specific test file: `php artisan test --filter=ClassName`
- Test files are located in `tests/Feature/`.
- Webcam feature tests: `tests/Feature/Webcam/`.

## Formatting (EditorConfig + ESLint)
- Indent: 2 spaces for `*.js`, `*.json`, `*.yml` (`.editorconfig`).
- Line endings: LF; final newline required.
- Max line length: 120 (warn), strings/templates are ignored.
- Blank lines inside blocks are required (`padded-blocks: always`).
- Allow single-line `if` without braces, but put body on next line (`nonblock-statement-body-position: below`).
- Curly braces enforced for multi-line or nested (`curly: multi-or-nest`).
- Object spacing: `{ foo }` (always).
- Array spacing: `[foo]` (never).
- Arrow parens/body: omit parens when possible; keep concise bodies.
- Up to 2 statements per line; no multiple empty lines (max 2).
- Unix linebreaks enforced (`.eslintrc`).

## Code Style
- Do not write AI slop code.
- If you see bad code, do not copy the same style; improve it while staying consistent with local patterns.
- Write self-explanatory code. Use clear variable and function names instead of comments.
- Comments only for non-obvious business logic or external API quirks.
- No redundant comments that repeat what the code does.
- No unnecessary abstractions.
- Keep functions focused and short.

## Imports & Modules
- Main process (`app/src`) uses CommonJS: `const x = require('x')`.
- Renderer (`app/renderer`) uses ES modules: `import x from 'x'`.
- Do not mix module systems inside a file.
- Keep external imports before internal/relative imports.
- `import` resolver supports `.js`, `.jsx`, `.vue`.

## Naming Conventions
- Vue components: PascalCase filenames and `name` fields (`App.vue`, `ControlBar`).
- JS modules in `app/src`: kebab-case filenames (`offline-mode.js`).
- Classes: PascalCase (`Logger`, `HeartbeatMonitor`).
- Variables/functions: camelCase; booleans use `is/has/should`.
- Constants: `UPPER_SNAKE` or PascalCase based on existing module (`ScreenshotsState`).
- IPC channels: `domain/action` (e.g. `tasks/sync`).
- Error IDs: uppercase codes like `ERTT500`, `HB001`.

## Vue SFC Style
- Section order: `<template>`, `<script>`, `<style>`.
- Use `export default { ... }` with `name`.
- Prefer `scoped` styles only when the component truly needs isolation.
- Use `lang="scss"` where styles match the rest of the app.
- Keep template indentation consistent with surrounding file.

## SCSS Style
- SCSS entrypoint: `app/renderer/scss/app.scss`.
- Element UI theme variables live in `app/renderer/scss/imports/_variables.scss`.
- Prefer existing variables (`$--color-*`, `$--font-*`) over new hard-coded values.
- Use `@import` style consistent with current files.

## State & Routing
- Vuex store is initialized in `app/renderer/js/storage/index.js`.
- Vue Router config is in `app/renderer/js/router/index.js`.
- Keep route names stable (e.g. `user.tasks`, `auth.login`).

## Error Handling & Logging
- Use `UIError` for user-facing issues in main process routes.
- Pass UIErrors to renderer: `request.send(code, { message, id, error })`.
- Use `Logger` (`app/src/utils/log.js`) for operational errors.
- `Logger.error` handles API errors, captures context, and reports to Sentry.
- Use `AppError` for internal errors that should always be captured.
- Renderer should check `req.code` and surface errors with Element UI dialogs/messages.

## IPC Conventions
- Define handlers via `router.serve('channel', async req => { ... })`.
- Use `req.send(status, payload)` to respond.
- Emit events with `router.emit('channel', payload)` when needed.
- Renderer uses `this.$ipc.request(...)` and `this.$ipc.serve(...)`.
- Keep payloads JSON-serializable; avoid sending class instances.

## Build Artifacts & Paths
- Renderer bundle outputs to `build/` (gitignored).
- Packaged apps output to `target/` (gitignored).
- Do not edit generated assets in `build/` or `target/`.

## Dependencies & Config
- Sentry release config in `.sentry.json` (used by `build-release`).
- Electron security flags are configured in `app/src/app.js`.
- Keep `config` access centralized in `app/src/base/config`.

## Cursor/Copilot Rules
- No Cursor rules found (`.cursor/rules/` or `.cursorrules`).
- No Copilot instructions found (`.github/copilot-instructions.md`).

## When In Doubt
- Follow patterns in neighboring files and keep changes minimal.
- Avoid large refactors unless requested.
- Update `AGENTS.md` if tooling or rules change.
