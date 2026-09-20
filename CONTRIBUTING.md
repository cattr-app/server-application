# Contributing to Cattr

Thank you for your interest in contributing to Cattr.

This guide describes both the technical development workflow and the rules used to review and accept changes into the Cattr server application.

The repository contains the Laravel backend, Vue frontend, module system, production container configuration, and release pipeline.

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

| Path                  | Purpose                                            |
|-----------------------|----------------------------------------------------|
| `app/`                | Laravel application code                           |
| `config/`             | Application configuration                          |
| `database/`           | Migrations, seeders, and factories                 |
| `routes/`             | API, web, and operational routes                   |
| `resources/frontend/` | Vue frontend application                           |
| `modules/`            | Backend modules                                    |
| `tests/`              | Backend unit and feature tests                     |
| `.root-fs/`           | Files added to the production container filesystem |
| `build/base/`         | apko definitions for runtime and builder images    |
| `build/packages/`     | Melange package definitions                        |
| `.github/workflows/`  | GitHub Actions workflows                           |
| `.helm/`              | Helm deployment files                              |

The production application is distributed as a single container containing the backend, compiled frontend, nginx, Laravel Octane, queue worker, Laravel Reverb, and scheduled jobs.

## Development requirements

Use the versions declared by the repository whenever possible.

| Dependency | Version                                                    |
|------------|------------------------------------------------------------|
| PHP        | 8.3                                                        |
| Laravel    | 10.x                                                       |
| Node.js    | `.nvmrc`, currently 18.20                                  |
| pnpm       | `packageManager` in `package.json`, currently 10.x         |
| Composer   | Composer 2                                                 |
| Database   | MySQL-compatible database                                  |

Corepack is required to use the pnpm version pinned by the repository.

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

Activate the repository Node.js version when using `nvm`:

```bash
nvm use
```

Enable Corepack and install frontend dependencies:

```bash
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

Production uses nginx and Laravel Octane with Swoole, with services supervised by s6-overlay.

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

ESLint configuration is defined by `.eslintrc.js`.

The frontend currently uses Vue 2.7, Vue Router 3, Vuex 3, Laravel Mix, and Webpack 5.

Generated frontend output should not be committed.

## Backend development

The backend is a Laravel 10 application running on PHP 8.3.

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

Cattr supports backend and frontend modules.

### Backend modules

Backend modules are stored under:

```text
modules/
```

Composer merges module-level Composer definitions from:

```text
modules/*/composer.json
```

using `wikimedia/composer-merge-plugin`.

The root `modules.json` contains backend module configuration.

The base repository currently does not enable additional modules by default.

Core code should not assume that an optional module is installed.

### Frontend modules

The base frontend module configuration is:

```text
resources/frontend/etc/modules.config.json
```

Additional configuration is merged in this order:

```text
modules.config.json
modules.<NODE_ENV>.json
modules.ci.json
modules.local.json
```

Later configuration overrides earlier values.

`modules.local.json` is intended for developer-specific configuration and is ignored by Git.

Package-based frontend modules are statically included during compilation when configured as:

```json
{
    "type": "package",
    "ref": "package-name",
    "enabled": true
}
```

`enabled` defaults to `true`.

## Container architecture

The application image is built in separate builder and runtime stages.

The builder image contains the tools required to install dependencies and compile the application.

The runtime image contains only the packages required to operate Cattr.

The Dockerfile uses:

```text
ghcr.io/cattr-app/server-runtime:builder-latest
ghcr.io/cattr-app/server-runtime:runtime-latest
```

A local application image can be built with:

```bash
docker build -t cattr-server .
```

The base images can be overridden with:

```text
BUILDER_BASE_IMAGE
BUILDER_BASE_IMAGE_TAG
RUNTIME_BASE_IMAGE
RUNTIME_BASE_IMAGE_TAG
```

Production images currently target Linux `amd64`.

## Runtime services

The production container uses s6-overlay to manage its services and initialization steps.

The runtime includes:

```text
nginx
Laravel Octane
Laravel Queue
Laravel Reverb
supercronic
```

Startup also prepares application state, applies migrations and the initial seeder, and creates the administrator account when required.

Changes to `.root-fs/` should therefore be tested using a production-style container build rather than only `php artisan serve`.

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

The builder contains additional development and compilation dependencies such as Composer, Node.js, npm, Git, and Corepack.

Corepack is packaged with Melange:

```text
build/packages/corepack.yaml
```

The generated APK is added to the builder image and is used to activate the pnpm version pinned by `package.json`.

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
6. Updates the `runtime-latest` and `builder-latest` aliases.
7. Builds the application with Buildah.
8. Publishes the release version and `latest` to `ghcr.io/cattr-app/server`.

Changes to the release pipeline should preserve reproducible application builds and avoid unnecessary rebuilding of unchanged base images.

## Docker Compose

The repository contains `docker-compose.yml`, but it reflects an existing deployment environment and expects an external Docker network named:

```text
web
```

It should not be assumed to be a standalone development or production deployment configuration.

For normal application development, use the local development setup described above unless working specifically on deployment or container infrastructure.

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
