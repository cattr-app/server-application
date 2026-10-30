# Contributing to Cattr

Thank you for your interest in contributing to Cattr.

This guide describes both the technical development workflow and the rules used to review and accept changes into the Cattr server application.

The repository contains the Laravel backend, Vue frontend, module system, production container configuration, and release pipeline.

For frontend and backend extension APIs, see the [developer guides](./docs/developer/README.md). The generated [HTTP API reference](https://docs.cattr.app/api/) is published separately.

For general questions and ideas, use [GitHub Discussions](https://github.com/orgs/cattr-app/discussions). For bugs and actionable feature requests, use [GitHub Issues](https://github.com/cattr-app/server-application/issues).

## Contribution workflow

An Issue is not required before opening a pull request.

If a pull request does not reference an existing Issue, its description must provide enough context for reviewers to understand the change without reconstructing the problem themselves.

For bug fixes, describe:

- what is currently wrong;
- how the problem can be reproduced;
- what behavior is expected;
- how the proposed change fixes it.

For new functionality, describe:

- what problem or use case the change addresses;
- why the functionality belongs in Cattr;
- the proposed behavior;
- any user-visible, API, configuration, or deployment changes.

Large contributions are welcome even when they were not discussed in advance. However, discussing substantial changes first can reduce the risk of spending significant effort on an approach that may not fit the project's direction.

For major or breaking changes, open an Issue or Discussion before implementation.

This includes changes that significantly affect:

- public APIs;
- database compatibility;
- deployment architecture;
- authentication or authorization;
- the module system;
- persistent data formats;
- compatibility with existing clients;
- other fundamental application behavior.

A formal RFC process is not currently required.

## Pull request scope

A pull request should normally address one logical task.

Avoid combining unrelated:

- bug fixes;
- refactoring;
- formatting;
- dependency updates;
- feature work.

Do not reformat unrelated files as part of a functional change.

Dependency updates should normally be submitted separately from application changes.

If a new dependency is necessary to implement a feature or fix, explain why it is needed and why the functionality should not reasonably be implemented using existing dependencies.

Maintainers may ask for a large pull request to be split before review or merge.

## Pull request requirements

Before a pull request can be merged, changes relevant to the pull request must pass the applicable:

- automated and manual tests;
- frontend linting and formatting checks;
- documentation updates.

Changes to user-visible behavior should update the corresponding user documentation where appropriate.

New or significantly changed UI sections must include screenshots in the pull request description so reviewers can evaluate the result without running the application locally.

For substantial new UI functionality, updating the client-facing documentation is strongly encouraged and may be required during review.

Backend bug fixes must include a test of the matching type that fails without the fix (see [Test types](#test-types)).

Database changes must include appropriate migrations.

Public API or configuration changes must be documented.

Changes should preserve backwards compatibility unless a breaking change has been discussed and accepted beforehand.

## Review and approval

Every pull request requires at least one approval from a Cattr maintainer before it can be merged.

The approval requirement applies to the project rather than to any particular individual maintainer. Project maintenance may change over time, and no contribution should depend on the continued availability of a specific person.

Maintainers may make small corrections directly to a contributor's branch when repository permissions allow it, for example to fix a typo or resolve a minor review issue.

Contributors should not rely on maintainers to complete unfinished work. Significant changes requested during review should normally be implemented by the contributor.

A technically correct contribution may still require changes or remain unmerged if it introduces disproportionate maintenance cost, conflicts with existing architecture, duplicates planned work, or does not fit the current direction of the project.

When this happens, maintainers should explain the reason during review whenever practical.

## Merge policy

Changes enter `main` through pull requests.

The repository uses **merge commits** when merging pull requests into `main`.

Squash merging is not used.

Rebase merging is not used.

This policy preserves the original contributor commits and their cryptographic signatures.

Merge commits may also be used between development branches when appropriate.

Direct pushes to `main` should not be used for normal development.

## Commit messages

Cattr uses [Conventional Commits](https://www.conventionalcommits.org/).

Because pull requests are not squash-merged, individual commits should have meaningful Conventional Commit messages.

Examples:

```text
feat: add project report filter
fix: prevent duplicate time intervals
refactor: simplify project permissions
docs: update installation instructions
test: cover offline synchronization
chore: update frontend dependencies
```

Keep commits focused and understandable independently of the pull request description.

Avoid placeholder history such as:

```text
fix
fix again
review fixes
wip
final fix
```

Prefer amending or reorganizing local commits before publishing them when doing so does not rewrite already shared and reviewed history.

### Optional local commit validation

Developers who use [mise](https://mise.jdx.dev/) can enable the
[hk](https://hk.jdx.dev/) `commit-msg` hook:

```bash
mise run setup-hooks
```

The hook validates Conventional Commit messages using `hk.pkl`. Mise manages
the hk version through `mise.toml`. Local hooks are optional and are not
required to contribute.

## Commit sign-off

Every contribution commit must include a Developer Certificate of Origin style sign-off.

Create signed-off commits with:

```bash
git commit -s
```

This adds a trailer similar to:

```text
Signed-off-by: Your Name <you@example.com>
```

By signing off a contribution, you certify that you have the right to submit it and to provide the rights required by the project and its Contributor License Agreement.

The sign-off must correspond to the contributor making the commit.

## Cryptographic commit signatures

All commits merged into `main` must also be cryptographically signed and verifiable by GitHub.

The DCO sign-off and cryptographic signature are separate requirements.

A sign-off:

```bash
git commit -s
```

adds the `Signed-off-by` declaration.

A cryptographic signature:

```bash
git commit -S
```

signs the Git commit object.

They can be used together:

```bash
git commit -S -s -m "fix: prevent duplicate time intervals"
```

Configure Git to sign commits with a GPG key associated with your GitHub account.

Submitted commits should appear as **Verified** on GitHub.

Do not rebase contributor commits as part of the merge process, because rebasing creates new commit objects and does not preserve the original cryptographic signatures.

## Contributor License Agreement

External contributions require acceptance of the Cattr Contributor License Agreement before they can be merged.

See [CLA.md](./CLA.md).

The CLA exists because Cattr may be distributed both under the project's public SSPL license and under separate commercial licensing terms.

The agreement ensures that the project owner has the rights required to use, modify, distribute, sublicense, and license contributed code as part of Cattr while defining the rights retained by the contributor.

The CLA and DCO serve different purposes:

- the CLA defines the rights granted to the project owner;
- the DCO sign-off confirms that the contributor has the right to make that grant;
- the cryptographic signature verifies the origin of the individual commit.

Contributors covered by a separate employment, contractor, or intellectual-property agreement with the project owner may be governed by that agreement instead.

## AI-assisted contributions

AI-assisted development is allowed.

There are no separate restrictions on the use of coding assistants, language models, code completion tools, or similar systems.

The contributor remains fully responsible for all submitted code.

In particular, the contributor must:

- understand every submitted change;
- be able to explain and maintain it;
- verify its correctness;
- verify its security implications;
- ensure appropriate test coverage;
- ensure that the submitted code can legally be contributed;
- ensure that generated or suggested code does not introduce incompatible licensing obligations.

The use of an AI tool does not transfer responsibility from the contributor to the tool or its provider.

## Backwards compatibility

Cattr is an existing application with deployed installations and external clients.

Contributions should therefore preserve compatibility by default.

Take particular care when changing:

- database schemas and migrations;
- public API behavior;
- API response formats;
- configuration variables;
- authentication flows;
- module interfaces;
- stored data;
- desktop-client integration;
- container behavior.

Breaking changes may be accepted, but they should first be discussed through an Issue or Discussion and should include a clear migration path where practical.

## Repository overview

The repository contains both the server-side application and the web frontend.

| Path                  | Purpose                                               |
|-----------------------|-------------------------------------------------------|
| `app/`                | Laravel application code                              |
| `bootstrap/app.php`   | Application setup: routing, middleware, and exceptions |
| `bootstrap/providers.php` | Service providers registered for the application  |
| `config/`             | Application configuration                             |
| `database/`           | Migrations, seeders, and factories                    |
| `routes/`             | API, web, and operational routes                      |
| `routes/console.php`  | Artisan commands and scheduled tasks                  |
| `resources/frontend/` | Vue frontend application                              |
| `modules/`            | Backend modules                                       |
| `tests/`              | Backend unit and feature tests                        |
| `runtime/`            | Runtime configuration for Caddy, PHP, and cron        |
| `init/`               | Container entrypoint and Pebble service configuration |
| `build/base/`         | apko definitions for runtime and builder images       |
| `build/packages/`     | Melange package definitions                           |
| `.github/workflows/`  | GitHub Actions workflows                              |
| `.helm/`              | Helm deployment files                                 |

The production application is distributed as a single container containing the backend, compiled frontend, Caddy, Laravel Octane, queue worker, Laravel Reverb, and scheduled jobs.

The production build is Wolfi-based: the builder uses Node.js 24 and the pnpm
version declared in `package.json`; the runtime uses PHP 8.3, Caddy, Pebble,
Laravel Octane, and Laravel Reverb.

## Development requirements

Use the versions declared by the repository whenever possible.

| Dependency | Version source                        |
|------------|---------------------------------------|
| PHP        | `.php-version`, `composer.json`        |
| Laravel    | `composer.json`                       |
| Node.js    | `.nvmrc`                              |
| pnpm       | `packageManager` in `package.json`    |
| Composer   | Composer 2.x                          |
| Database   | MySQL-compatible database             |

Mise is an optional way to install Node.js and pnpm using these version
declarations. PHP is intentionally not managed by this repository's
`mise.toml`; use a PHP version manager or a container-based environment.

The PHP installation must provide the extensions required by `composer.json`, including GD, JSON, OpenSSL, PDO, and ZIP. When using MySQL locally, the corresponding PDO MySQL driver is also required.

## Getting started

Clone the repository:

```bash
git clone https://github.com/cattr-app/server-application.git
cd server-application
```

Create your local environment file:

```bash
cp .env.example .env
```

Install backend dependencies:

```bash
composer install
```

Generate the application key:

```bash
php artisan key:generate
```

Install Node.js, pnpm, and frontend dependencies with mise, if you use it:

```bash
mise install
pnpm install --frozen-lockfile
```

Mise reads Node.js from `.nvmrc` and pnpm from the `packageManager` field
in `package.json`.

Other Node.js version managers remain supported. For example, with nvm
and Corepack:

```bash
nvm use
corepack enable
pnpm install --frozen-lockfile
```

## Database setup

Configure the database connection in `.env`.

A typical local MySQL configuration is:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cattr
DB_USERNAME=cattr
DB_PASSWORD=password
```

Create the database before applying migrations.

Initialize the schema and required application data:

```bash
php artisan migrate --seed --seeder=InitialSeeder
```

The initial seeder is required for a working Cattr installation.

Create the storage symlink:

```bash
php artisan storage:link
```

Create an administrator account:

```bash
php artisan cattr:make:admin
```

Administrator defaults can be configured through:

```dotenv
APP_ADMIN_EMAIL=admin@cattr.app
APP_ADMIN_PASSWORD=password
APP_ADMIN_NAME=Admin
```

The built-in defaults are intended only for local development.

Do not use default credentials on a publicly accessible installation.

## Running locally

Start the backend:

```bash
php artisan serve
```

Start the frontend watcher in another terminal:

```bash
pnpm watch
```

The application is available by default at:

```text
http://127.0.0.1:8000
```

Some functionality requires additional processes.

Start the queue worker:

```bash
php artisan queue:work
```

Start the realtime server:

```bash
php artisan reverb:start
```

When working on scheduled jobs:

```bash
php artisan schedule:work
```

The local development server does not reproduce the complete production runtime.

Production uses Caddy on port 8080 and Laravel Octane with Swoole. Pebble supervises
Caddy, Octane, Reverb, the queue worker, and scheduled jobs in the rootless runtime.

## Frontend development

The frontend lives under:

```text
resources/frontend/
```

Build once in development mode:

```bash
pnpm dev
```

Watch source files:

```bash
pnpm watch
```

Create a production frontend build:

```bash
pnpm prod
```

Run frontend validation:

```bash
pnpm lint
```

Frontend formatting is defined by `prettier.config.js`.

ESLint configuration is defined by `eslint.config.js` (ESLint flat config).

The frontend currently uses Vue 2.7, Vue Router 3, Vuex 3, Laravel Mix, and Webpack 5.

Generated frontend output should not be committed.

## Backend development

The backend is a Laravel 13 application running on PHP 8.3.

Available Artisan commands can be inspected with:

```bash
php artisan
```

Generate Laravel IDE helpers:

```bash
composer dumphelpers
```

Generate the database entity-relationship diagram:

```bash
composer dumperd
```

Generated IDE helper files and `erd.svg` should not be committed.

When modifying the database schema, create a new migration instead of modifying migrations that may already have shipped.

When changing API behavior, check whether the corresponding API documentation also needs to be updated.

## Tests

Backend tests live under:

```text
tests/Unit/
tests/Feature/
```

`mockery/mockery` is kept in `require-dev` even though the suite does not call it
directly: Laravel's console test helper `$this->artisan()` (`PendingCommand`) needs it
for output expectations, and `$this->mock()` / `$this->partialMock()` / `$this->spy()`
helpers depend on it as well.

Create a testing environment:

```bash
cp .env.testing.example .env.testing
```

Configure a separate testing database.

The example configuration uses:

```dotenv
DB_DATABASE=cattr_tests
```

Generate a testing application key if necessary:

```bash
php artisan key:generate --env=testing
```

Run all backend tests:

```bash
vendor/bin/phpunit
```

Run unit tests:

```bash
vendor/bin/phpunit --testsuite Unit
```

Run feature tests:

```bash
vendor/bin/phpunit --testsuite Feature
```

Coverage requires Xdebug with coverage mode enabled.

### Test types

Most application logic lives in controllers, policies, Eloquent queries and observers, so feature tests are the default. Unit tests are used narrowly.

| Type                    | What it covers                                                                                                                                                  | How to recognise it                                                                  |
| ----------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------ |
| Feature, HTTP (default) | everything that goes through a route: authorization and policies, FormRequest validation, filters, response shape, database changes, dispatched events and jobs | the test calls an endpoint                                                           |
| Feature, no HTTP        | artisan commands, seeders, queued jobs, listeners, observers                                                                                                    | needs the database or the application container, but there is no route               |
| Unit                    | pure logic without the database: enums and their rules, duration and timezone calculations in reports, module flag parsing, helpers                             | extends `PHPUnit\Framework\TestCase`, touches neither the database nor the container |

Rule of thumb: if a test needs the database or the application container, it is a feature test. If the logic can be checked by passing input and comparing the output, it is a unit test.

Unit tests are worth it where logic has many input combinations that would be slow to cover through HTTP; a table-driven unit test covers them in milliseconds. Example: `ScreenshotsState` with global overrides, the viewer's role and the lock flag.

Do not:

- mock Eloquent or repositories to test a controller "in isolation": such tests check how the code calls the ORM, not what it does;
- chase a coverage percentage with unit tests: endpoint coverage matters more.

Every backend bug fix comes with a test of the matching type that reproduces the bug.

### Endpoint coverage

`tests/Feature/Routes/GetRoutesSmokeTest.php` calls every registered `GET` route as an admin on seeded data and fails if any of them returns a 5xx. A new `GET` route with a path parameter needs a seeded value in the test; a route that cannot be called this way goes to the test's exclusion list with a reason.

The smoke test only catches crashes. To list the routes that no feature test calls, run:

```bash
php scripts/route-coverage.php
```

It runs the Feature suite with `ROUTE_COVERAGE_LOG` set, so that `tests/TestCase.php` records every matched route, and prints the remaining routes as a Markdown checklist. The smoke test does not count as coverage. Pass `--log=<file>` to report from the log of an earlier run.

Run frontend validation with:

```bash
pnpm lint
```

Before submitting a contribution, run the tests and validation relevant to the changed code.

## Code style

Follow the style of the surrounding code.

Basic editor settings are defined in `.editorconfig`.

Frontend JavaScript and Vue code is validated by ESLint and Prettier.

The current Prettier configuration uses:

```text
Print width:     120
Indentation:     4 spaces
Quotes:          single
Semicolons:      enabled
Line endings:    LF
Trailing commas: enabled
```

PHP code should follow the existing project style.

Avoid unrelated formatting changes in functional pull requests.

## Dependencies

PHP dependencies are managed with Composer.

Commit changes to `composer.lock` together with dependency changes.

For example:

```bash
composer require vendor/package
```

Frontend dependencies are managed with pnpm.

Commit changes to `pnpm-lock.yaml` together with dependency changes.

For example:

```bash
pnpm add package
```

For development-only dependencies:

```bash
pnpm add -D package
```

Do not manually edit lockfiles.

Frontend dependency installation must remain reproducible with:

```bash
pnpm install --frozen-lockfile
```

The frontend workspace layout is defined in `pnpm-workspace.yaml`. Update it when adding or moving workspace packages.

Do not rely on transitive dependencies being available at the project root. If Cattr imports a package directly, declare that package as a direct dependency or development dependency as appropriate.

New production dependencies should be justified in the pull request description.

Consider:

- why the dependency is needed;
- whether existing project dependencies can already solve the problem;
- maintenance status;
- security history;
- transitive dependency cost;
- licensing compatibility.

Routine dependency upgrades should normally be submitted separately from unrelated feature or bug-fix work.

## Module system

Cattr always initializes built-in frontend modules. External frontend and backend modules
are bundled or installed with the application, but **disabled by default**.

Enable each external module independently with an explicit environment variable:

```text
MODULE_FRONTEND_AMAZING_CAT_REDMINE_INTEGRATION_ENABLED=true
MODULE_BACKEND_CATTR_REDMINE_INTEGRATION_ENABLED=true
MODULE_FRONTEND_AMAZING_CAT_GITLAB_INTEGRATION_ENABLED=true
MODULE_BACKEND_CATTR_GITLAB_INTEGRATION_ENABLED=true
MODULE_FRONTEND_AMAZING_CAT_DEMO_MODULE_ENABLED=true
MODULE_BACKEND_CATTR_DEMO_ENABLED=true
```

Only the exact lowercase string `true` enables an external module. Missing variables,
`false`, `1`, and other values disable it. Backend and frontend activation flags
are resolved by Laravel's `artisan config:cache` into `bootstrap/cache/config.php`
when the container starts. A changed container environment takes effect after the
container is recreated; no image rebuild is needed. Set backend flags
consistently for the application, queue, scheduler, and migration roles.

The flag name is the module identifier converted to upper snake case with the rules in
[docs/dev/module-activation-flags.md](docs/dev/module-activation-flags.md): `CattrRedmineIntegration`
(backend `module.json` name) becomes `MODULE_BACKEND_CATTR_REDMINE_INTEGRATION_ENABLED`,
`AmazingCat_RedmineIntegration` (frontend `ModuleConfig.moduleName`) becomes
`MODULE_FRONTEND_AMAZING_CAT_REDMINE_INTEGRATION_ENABLED`. The same algorithm is implemented in
`App\Services\ModuleActivatorService::flagName()` and `resources/frontend/core/utils/moduleFlagName.js`
and both are tested against `tests/fixtures/module-flag-names.json`. Frontend and backend modules
are installed and enabled independently and do not depend on one another's flags.

Backend module discovery still reads `module.json` for names and providers. Its `active` field
does **not** control activation, and `modules*.json` files are not consulted. Artisan
`module:enable` / `module:disable` refuse to run; `module:delete` and the activator reset only log
a warning that nothing changed. The legacy `modules` database table has been dropped.

External frontend package registration is build-time configuration in
`resources/frontend/etc/modules.config.json` (with the existing optional overlays). For example:

```json
{
    "AmazingCat_RedmineIntegration": {
        "type": "package",
        "ref": "@amazingcat/cattr-redmine-integration"
    }
}
```

The key of each entry must equal the package's `ModuleConfig.moduleName`: the frontend derives the
flag name from that key before the chunk is downloaded.

These files **only determine what gets bundled**, not what gets enabled. Package modules are
emitted as separate webpack chunks and are imported lazily only when their runtime flag is
`true`, so disabled modules do not ship in the main bundle. An optional module must already
be installed when building the image; adding an env flag cannot install missing code. Local
frontend modules are discovered in `resources/frontend/vendor_modules/<vendor>/<module>/module.init.js`;
`ModuleConfig.enabled` has no effect on activation. Internal frontend modules always initialize.

### Installing backend modules

Backend modules are ordinary Composer packages of type `laravel-module`; `nwidart/laravel-modules`
discovers `module.json` both in `modules/` and in `vendor/*/*`. The Composer merge plugin is no
longer used, so a directory dropped into `modules/` does not get its dependencies or autoload
installed; install modules through Composer instead:

```bash
# published package
composer require cattr/redmine_integration-module

# local checkout, symlinked for development (any path outside the repository works)
composer config repositories.redmine path ../redmine-integration
composer require cattr/redmine_integration-module:@dev
```

The path repository is a local setting; do not commit it. Then set
`MODULE_BACKEND_<FLAG_NAME>_ENABLED=true` in `.env`, run `php artisan migrate` for the module
migrations and restart Octane. `php artisan module:list` shows what was discovered and whether
the flag enabled it.

### Installing frontend modules

Frontend modules are npm packages. Add the package (or link a local checkout) and register it in
`modules.config.json` (or a local overlay):

```bash
pnpm add @amazingcat/cattr-redmine-integration
# or, for a local checkout
pnpm link ../redmine-integration/frontend
```

Then set `MODULE_FRONTEND_<FLAG_NAME>_ENABLED=true` and rebuild the assets (`pnpm prod` or
`pnpm watch`).

### Building an image with modules

The official `Dockerfile` installs dependencies only from the repository's own `composer.json`,
`package.json` and lockfiles, so the published `ghcr.io/cattr-app/server-application` image
contains **no external modules**. Setting `MODULE_*_ENABLED=true` on it has no effect: there is
nothing to enable. To run modules in a container, build your own image from a checkout of the
release you deploy.

Copy the official `Dockerfile` (for example to `Dockerfile.modules`) and add one step to the
builder stage, right after `COPY --chown=10000:10000 . /opt/cattr/app` and before the
`composer install` step:

```dockerfile
# Space-separated lists; leave a list empty to skip that side.
ARG COMPOSER_MODULES="cattr/redmine_integration-module"
# Entries are <ModuleConfig.moduleName>=<npm package>.
ARG FRONTEND_MODULES="AmazingCat_RedmineIntegration=@amazingcat/cattr-redmine-integration"

RUN set -eux; \
    if [ -n "${COMPOSER_MODULES}" ]; then \
        composer require --no-interaction --no-install ${COMPOSER_MODULES}; \
    fi; \
    for entry in ${FRONTEND_MODULES}; do \
        name="${entry%%=*}"; package="${entry#*=}"; \
        pnpm add --lockfile-only "${package}"; \
        node -e ' \
            const fs = require("fs"); \
            const file = "resources/frontend/etc/modules.config.json"; \
            const config = JSON.parse(fs.readFileSync(file, "utf8")); \
            config[process.argv[1]] = { type: "package", ref: process.argv[2] }; \
            fs.writeFileSync(file, JSON.stringify(config, null, 4) + "\n"); \
        ' "${name}" "${package}"; \
    done
```

The step only changes the manifests, lockfiles and `modules.config.json`; the unchanged
`composer install`, `pnpm install --frozen-lockfile` and `pnpm prod` steps that follow then
install the modules and bundle each frontend module as its own lazy chunk:

- `composer require --no-install` adds the backend package (a Composer package of type
  `laravel-module`) to `composer.json` and `composer.lock` without installing it, so the
  following `composer install` and optimized autoloader include it;
- `pnpm add --lockfile-only` adds the frontend package to `package.json` and `pnpm-lock.yaml`,
  so `pnpm install --frozen-lockfile` still passes;
- the key written to `resources/frontend/etc/modules.config.json` must equal the package's
  `ModuleConfig.moduleName`, otherwise the frontend derives a different flag name and never
  loads the chunk.

Build the image and pass other module lists as build arguments if needed:

```bash
podman build -f Dockerfile.modules \
    --build-arg COMPOSER_MODULES="cattr/<module>" \
    --build-arg FRONTEND_MODULES="<ModuleConfig.moduleName>=@amazingcat/<package>" \
    -t cattr-server-modules .
```

Then enable the modules in the container environment (for every role that runs the backend:
application, queue, scheduler and migration) and recreate the containers:

```text
MODULE_BACKEND_<FLAG_NAME>_ENABLED=true
MODULE_FRONTEND_<FLAG_NAME>_ENABLED=true
```

Module migrations run with the regular `migrate` role or start-up migration. To check the result:

```bash
podman run --rm --env-file .env cattr-server-modules exec php artisan module:list
```

The module must be listed as enabled; in the browser, its frontend chunk is requested after login
when its frontend flag is `true`.

## Container architecture

The application image is built in separate builder and runtime stages.

The builder image contains the tools required to install dependencies and compile the application.

The runtime image contains only the packages required to operate Cattr.

The Dockerfile uses:

```text
ghcr.io/cattr-app/server-runtime:builder-latest
ghcr.io/cattr-app/server-runtime:runtime-latest
```

A local application image can be built with Podman:

```bash
podman build -t cattr-server .
```

The final image runs as UID/GID `10000`, uses `/opt/cattr/app` as its working
directory, and stores mutable Laravel data under `/opt/cattr/app/storage`.
The image exposes Caddy on `8080`, Reverb on `8081`, and Octane on `8090`.

The base images can be overridden with:

```text
BUILDER_BASE_IMAGE
BUILDER_BASE_IMAGE_TAG
RUNTIME_BASE_IMAGE
RUNTIME_BASE_IMAGE_TAG
```

Production images currently target Linux `amd64`.

## Runtime services

The production container uses Pebble to manage its services and initialization steps.

The runtime includes:

```text
Caddy
Laravel Octane
Laravel Queue
Laravel Reverb
supercronic
```

Startup also prepares application state, applies migrations and the initial seeder, and creates the administrator account when required.

Changes to `runtime/`, `init/`, `Dockerfile`, or the base-image definitions should
therefore be tested using a production-style container build rather than only
`php artisan serve`.

Operational endpoints include:

```text
GET /actuator/health/liveness
GET /actuator/health/readiness
GET /actuator/prometheus
```

## Base images

Runtime and builder base images are defined with apko:

```text
build/base/runtime/apko.yaml
build/base/builder/apko.yaml
```

The Wolfi builder image contains Composer, PHP 8.3 builder extensions, Node.js 24,
Git, OpenSSH, and Corepack. The runtime image contains PHP 8.3, Caddy, Pebble,
supercronic, and the packages needed to run Cattr.

Corepack is packaged with Melange:

```text
build/packages/corepack.yaml
```

The generated APK is added to the builder image and Corepack activates the pnpm
version pinned by `package.json`.

When changing base-image dependencies, modify the relevant apko or Melange configuration instead of installing additional system packages in the application Dockerfile unless there is a specific reason to do otherwise.

## Release pipeline

Releases are built by:

```text
.github/workflows/release.yml
```

The workflow runs for tags matching:

```text
v*
```

The release process:

1. Calculates content-derived identities for runtime and builder images.
2. Reuses immutable base images when their inputs have not changed.
3. Builds the Corepack APK with Melange when required.
4. Builds missing base images with apko.
5. Publishes base images to `ghcr.io/cattr-app/server-runtime`.
6. Updates `runtime-latest` and `builder-latest` only for stable releases.
7. Builds the application with Buildah.
8. Publishes the exact application version, and also `latest` for stable releases.

Prereleases (for example, `v4.1.0-rc.1`) use exact-version application tags and
content-hash runtime/builder tags without moving any `*-latest` aliases.
Stable releases (for example, `v4.1.0`) also move the application `latest` and
base-image `runtime-latest`/`builder-latest` aliases. Unsupported `v*` tags,
including SemVer build metadata (`+...`, which is incompatible with OCI tags),
fail validation before images are built or published.

The `Release checks` workflow tests tag classification and lints the release
workflow for pull requests changing the release pipeline.

Changes to the release pipeline should preserve reproducible application builds and avoid unnecessary rebuilding of unchanged base images.

## Docker Compose

`docker-compose.yml` is a standalone local container setup. It builds the
application image from the current `Dockerfile`, starts a Percona-compatible
MySQL database, and waits for the database health check before starting Cattr.
It does not require a pre-created external network.

Build and start the stack with Docker Compose or the equivalent Podman command:

```bash
podman-compose up -d --build
```

The application is available on `http://127.0.0.1:8080`. The database is
published on port `3306` for local tooling. The Compose service mounts the
named volume `backend_storage` at `/opt/cattr/app/storage`; the database uses
the separate `database` volume.

Useful commands:

```bash
podman-compose logs -f app
podman-compose ps
podman-compose down
```

`down` preserves named volumes. Use `podman-compose down -v` only when a clean
database and empty Laravel storage volume are intentionally required.

The container runs as UID/GID `10000`. Its entrypoint creates the writable
Laravel directories under `/opt/cattr/app/storage`, including logs, framework
cache/sessions/views, screenshots, and module/public data.

## Documentation

When changing developer workflows, update this file.

When changing product behavior, configuration, installation, or public APIs, update the corresponding project documentation.

Relevant resources:

- [Cattr Documentation](https://docs.cattr.app)
- [API Documentation](https://api.docs.cattr.app)

Keep the root `README.md` focused primarily on Cattr as a product.

Detailed development, build, and contribution instructions belong here.

## License

Cattr Server is distributed under the [Server Side Public License 1.0](./LICENSE).

External contributions are additionally subject to the [Cattr Contributor License Agreement](./CLA.md).
