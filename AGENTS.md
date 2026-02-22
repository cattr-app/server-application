# AGENTS.md

## Purpose

This file guides agentic coding work in this repository.
Prefer existing patterns; avoid reformatting unrelated code.
Update this file if new tooling or conventions change.

## Repo Overview

-   Laravel 10 backend API, PHP 8.2, served via Laravel Octane.
-   Vue 2 frontend lives under `resources/frontend/`, built with Laravel Mix + Webpack.
-   Modular extension system in `modules/` (nwidart/laravel-modules).
-   Real-time events via Laravel Reverb (WebSockets).
-   Auth via Laravel Sanctum (bearer tokens).
-   Response shaping via `flugger/laravel-responder` (`responder()->success()` / `responder()->error()`).


## Code Style
- Do not write AI slop code.
- If you see bad code, do not copy the same style; improve it while staying consistent with local patterns.
- Write self-explanatory code. Use clear variable and function names instead of comments.
- Comments only for non-obvious business logic or external API quirks.
- No redundant comments that repeat what the code does.
- No unnecessary abstractions.
- Keep functions focused and short.

## Project Structure

-   `app/Http/Controllers/Api/` — API controllers; all extend `ItemController`.
-   `app/Http/Controllers/Api/ItemController.php` — base CRUD methods: `_index`, `_show`, `_create`, `_edit`, `_destroy`, `_count`.
-   `app/Http/Requests/` — form requests; all extend `CattrFormRequest` and implement `_authorize()` + `_rules()`.
-   `app/Models/` — Eloquent models with SoftDeletes, Enums as casts, GlobalScopes.
-   `app/Policies/` — Laravel policies, one per resource; use `HandlesAuthorization` + `before()` for admin bypass.
-   `app/Enums/` — PHP 8.1 backed enums (int or string).
-   `app/Exceptions/Entities/` — typed domain exceptions (e.g. `AuthorizationException`, `NotFoundHttpException`).
-   `app/Exceptions/Handler.php` — registers Sentry reporting; converts exceptions to JSON via `responder()`.
-   `app/Services/` — service classes for complex domain logic (no static state).
-   `database/factories/` — Eloquent model factories.
-   `database/migrations/` — standard Laravel migrations.
-   `database/seeds/` — seeders.
-   `routes/api.php` — all API routes; grouped by resource with named routes (`auth.login`, `projects.list`).
-   `tests/Feature/` — feature tests, mirroring controller namespaces.
-   `tests/Unit/` — unit tests for isolated classes.
-   `tests/Factories/` — test-only fluent factory helpers (not Eloquent factories).
-   `resources/frontend/` — Vue 2 SPA source.

## Commands

### PHP / Laravel

```bash
# Install dependencies
composer install

# Run all tests
php artisan test

# Run a single test class
php artisan test --filter=LoginTest

# Run a single test method
php artisan test --filter=LoginTest::test_success

# Run a specific test file directly
php artisan test tests/Feature/Auth/LoginTest.php

# PHP CodeSniffer (PSR-2)
./vendor/bin/phpcs

# Fix auto-fixable CS violations
./vendor/bin/phpcbf
```

### JS / Frontend

```bash
# Install dependencies
yarn

# Lint JS/Vue (runs on pre-commit via husky)
yarn lint

# Build frontend for development
yarn dev

# Build with watch mode
yarn watch

# Build for production
yarn prod
```

### Commit Conventions

Commits must follow Conventional Commits (`@commitlint/config-conventional`).
Examples: `feat: add project phases endpoint`, `fix: validate interval overlap`.

## PHP Code Style (PSR-2 + project conventions)

### Formatting

-   Indent: 4 spaces (no tabs).
-   Max line length: 120 characters.
-   LF line endings; UTF-8; final newline required.
-   Class and method opening braces on **next line**; control-flow braces on **same line**.
-   One blank line between methods; no blank line after class opening brace.

### Imports

-   One `use` statement per line; alphabetically sorted within a group.
-   Group order: PHP built-ins → Laravel/vendor → App namespaces → aliases.
-   No unused imports; no wildcard imports.
-   Facade aliases (`Filter`, `CatEvent`, `DB`, `Auth`, `Cache`) are registered — import them without the full namespace when used as facades.

### Naming

-   Classes: `PascalCase` (e.g. `ProjectController`, `UserFactory`).
-   Methods/variables: `camelCase`; booleans use `is`/`has`/`can` prefix.
-   Constants: `UPPER_SNAKE_CASE`.
-   Database columns: `snake_case`; match Eloquent attribute names exactly.
-   Route names: `resource.action` (e.g. `projects.create`, `auth.logout`).
-   Test methods: `test_` prefix + snake_case description (e.g. `test_create_as_admin`).
-   Private test fields declared at class top with `/** @var Type $name */` doc blocks.

### Types

-   Declare return types and parameter types on all public/protected methods.
-   Use PHP 8.1 enums for finite value sets (always backed: `enum Role: int`).
-   Use `Attribute` casts in models for computed properties.
-   Prefer `throw_unless` / `throw_if` helpers over manual `if (!$x) throw`.

### Controllers

-   Every public API controller extends `ItemController`; delegate to `_index`, `_show`, `_create`, `_edit`, `_destroy`.
-   Use `Filter::listen(...)` to hook into query/action pipelines; do not override base methods.
-   Use `CatEvent::listen(...)` for before/after action side effects.
-   Return `JsonResponse`; use `responder()->success($data)->respond()` and `responder()->success()->respond(204)` for deletes.

### Form Requests

-   Extend `CattrFormRequest`; implement `_authorize(): bool` and `_rules(): array`.
-   Authorization goes through `Filter::process(Filter::getAuthFilterName(), ...)` — do not call `$this->user()->can(...)` directly in `authorize()`.
-   Use `Illuminate\Validation\Rules\Enum` for enum validation.

### Models

-   Use `SoftDeletes` for all domain entities.
-   Declare `$fillable` or `$guarded`; never use unguarded mass assignment in production code.
-   Type-hint relationships (`HasMany`, `BelongsToMany`, etc.) and add `@property` phpdoc blocks at the top of the class for IDE support.
-   Global scopes go in `app/Scopes/`; register in model's `booted()`.

### Policies

-   One policy per resource in `app/Policies/`.
-   Always implement `before(User $user): ?bool` to grant admins full access.
-   Keep policy methods side-effect free; no DB writes inside a policy.

### Error Handling

-   Throw typed exceptions from `app/Exceptions/Entities/` for known error states.
-   `AuthorizationException` with `ERROR_TYPE_FORBIDDEN` / `ERROR_TYPE_UNAUTHORIZED` for auth failures.
-   `NotFoundHttpException` / `AccessDeniedHttpException` (Symfony) for HTTP-layer 404/403.
-   All unhandled `Throwable` are captured by Sentry in `Handler::register()` — do not swallow exceptions silently.
-   Error response shape: `{ "error": { "code": "...", "message": "..." } }` via `CattrErrorResponse`.

## JS / Vue Code Style

### Formatting (enforced by Prettier + ESLint)

-   Indent: 4 spaces; single quotes; semicolons required; trailing commas everywhere; print width 120.
-   LF line endings.
-   Arrow functions: omit parentheses for single params (`x => x + 1`).

### Vue SFC

-   Section order: `<template>`, `<script>`, `<style>`.
-   Attribute order enforced by ESLint `vue/attributes-order`: DEFINITION → LIST_RENDERING → CONDITIONALS → ... → EVENTS → CONTENT.
-   Use `export default { name: 'ComponentName', ... }`.
-   Component names: PascalCase file and `name` field.

### Imports

-   External packages before internal/relative imports.
-   `import` for frontend ES modules; no CommonJS `require` in `resources/frontend/`.

## Testing Conventions

-   All tests use `DatabaseTransactions` (rolled back after each test — no manual cleanup needed).
-   Use fluent test factories from `tests/Factories/` (e.g. `UserFactory::refresh()->asAdmin()->withTokens()->create()`).
-   Authentication in tests: `$this->actingAs($user)->postJson(...)`.
-   Use `self::HTTP_*` constants from `TestCase` instead of raw status codes.
-   Assert error responses with `$response->assertError($code, 'error.type')`.
-   Each test class has a `private const URI` pointing to the endpoint under test.
-   `setUp()` creates all needed fixtures; never share mutable state between test methods.

## No Cursor/Copilot Rules Found

No `.cursorrules`, `.cursor/rules/`, or `.github/copilot-instructions.md` exist in this repo.
