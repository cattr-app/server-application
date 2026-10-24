# Migration to the Laravel 11+ application skeleton

Status: done

## Goal

Move the application bootstrap from the pre-11 layout (`app/Http/Kernel.php`,
`app/Console/Kernel.php`, `app/Exceptions/Handler.php` bound in `bootstrap/app.php`,
framework and app providers listed in `config/app.php`, `RouteServiceProvider`,
`EventServiceProvider`, `AuthServiceProvider`, `BroadcastServiceProvider`) to the slim
skeleton that `laravel/laravel` ships since 11.x: `Application::configure()` in
`bootstrap/app.php`, `bootstrap/providers.php`, `routes/console.php`, convention-based
policy and event discovery.

## Constraints (non-negotiable)

- No behavior change. Same middleware, same order, same route prefixes and names,
  same exception responses (flugger/responder envelopes), same schedule.
- Environment variable names stay as they are (`CACHE_DRIVER`, `BROADCAST_DRIVER`,
  `SESSION_DRIVER`, `QUEUE_CONNECTION`, ...). Deployments and Helm charts depend on them.
- Octane (swoole) and nwidart/laravel-modules keep working; modules register their
  own providers through package discovery and `config/modules.php`, nothing to do there.
- The PHPUnit suite stays green (322 tests at the time of writing) and new structural
  assertions are added where discovery replaces explicit registration.
- Work only in `tests/`, `bootstrap/`, `app/Providers`, `app/Http/Kernel.php`,
  `app/Console/Kernel.php`, `artisan`, `public/index.php`, `routes/console.php`,
  `config/app.php` for WP1. Anything else is out of scope for WP1.

## Current state (what moves where)

| Today | Target |
|---|---|
| `bootstrap/app.php` binds `App\Http\Kernel`, `App\Console\Kernel`, `App\Exceptions\Handler` | `Application::configure(basePath: dirname(__DIR__))->withRouting(...)->withMiddleware(...)->withExceptions(...)->withSingletons([...])->create()` |
| `App\Http\Kernel::$middleware` (8 global middleware, ordered) | `$middleware->use([...])` with the identical list and order |
| `$middlewareGroups['web']` = EncryptCookies, AddQueuedCookiesToResponse, StartSession, ShareErrorsFromSession, SubstituteBindings | `$middleware->group('web', [...])` identical list (note: no CSRF middleware in the group today, keep it that way) |
| `$middlewareGroups['api']` = SubstituteBindings, EnsureFrontendRequestsAreStateful | `$middleware->group('api', [...])` identical list (no throttle, keep it that way) |
| `$routeMiddleware` aliases | framework defaults cover `auth`, `auth.basic`, `can`, `throttle`, `signed`; add `abilities`, `ability` via `$middleware->alias([...])`; check `bindings` is still needed by any route (`grep -rn "'bindings'" routes app modules`) and add it if so |
| `App\Http\Middleware\TrimStrings` | if the class only customizes `$except`, drop it and use `$middleware->trimStrings(except: [...])`; otherwise keep the class in `use([...])` |
| `RouteServiceProvider::map()` (api prefix `api` + `api` group, web, actuator prefix/name) | `withRouting(web:, api:, apiPrefix: 'api', channels:, commands:, then: fn () => Route::prefix('actuator')->name('actuator.')->group(base_path('routes/actuator.php')))`. Controllers are referenced by class everywhere; verify no string `Controller@action` remains before dropping the namespace |
| `App\Console\Kernel::schedule()` (6 entries) and `commands()` loading `app/Console/Commands` | schedule in `routes/console.php` via `Schedule::` facade (or `->withSchedule()`), identical expressions, `when(Settings::...)` included; commands are auto-loaded from `app/Console/Commands` by `withCommands()` defaults |
| `App\Exceptions\Handler` (ConvertsExceptions from responder, custom `context()`, `render()`, `$dontReport`, `$dontFlash`) | keep the class as is; bind it with `->withSingletons([ExceptionHandler::class => App\Exceptions\Handler::class])` after `withExceptions()`. Rewriting `render()`/`context()` into the `Exceptions` configurator is a separate, later task |
| `EventServiceProvider` (observers for `User`, `TimeInterval`; event discovery on) | observers registered in `AppServiceProvider::boot()` (or `#[ObservedBy]` on the models); event discovery stays on by default (`withEvents()`), verify `InvitationCreated -> SendInvitationMail` with `php artisan event:list` |
| `AuthServiceProvider::$policies` (9 explicit mappings) | all policies follow the `App\Policies\{Model}Policy` convention, so rely on discovery; add a unit test asserting `Gate::getPolicyFor()` for each of the 9 models |
| `BroadcastServiceProvider` (`Broadcast::routes(['middleware' => ['auth:sanctum']])` + `routes/channels.php`) | `->withBroadcasting(__DIR__.'/../routes/channels.php', ['middleware' => ['auth:sanctum']])` |
| `config/app.php` `providers` (framework list + Intervention, Prometheus, 10 app providers) | delete the array. Framework providers are implicit; Intervention comes through package discovery (verify `extra.laravel.providers` in their composer.json, otherwise list them in `bootstrap/providers.php`); app providers go to `bootstrap/providers.php`: AppServiceProvider, FilterServiceProvider, CatEventServiceProvider, SettingsServiceProvider, ScreenshotsServiceProvider, AttachmentServiceProvider, PrometheusServiceProvider |
| `config/app.php` `aliases` (`Facade::defaultAliases()->merge([...])`) | keep only the custom aliases array (`Settings`, `Filter`, `CatEvent`, `Responder`, `Transformation`); the framework merges its defaults itself |
| `artisan`, `public/index.php` (old kernel boilerplate) | skeleton versions: `$app->handleCommand(new ArgvInput)` and `$app->handleRequest(Request::capture())`, keep the maintenance-file check |
| `tests/CreatesApplication.php`, `TestCase::createApplication()` seeding `installed=true` | delete the trait; `Illuminate\Foundation\Testing\TestCase` boots `bootstrap/app.php` itself; set `Settings::scope('core')->set('installed', true)` in `setUp()` after `parent::setUp()` |

Files to delete at the end of WP1: `app/Http/Kernel.php`, `app/Console/Kernel.php`,
`app/Providers/RouteServiceProvider.php`, `app/Providers/EventServiceProvider.php`,
`app/Providers/AuthServiceProvider.php`, `app/Providers/BroadcastServiceProvider.php`,
`tests/CreatesApplication.php`. Grep for every removed class name in `app config routes
tests modules bootstrap` before deleting.

## Work packages

### WP1: bootstrap core (one agent, sequential)

Everything in the table above. Deliverables: green suite, plus the checks below.

### WP2: config catch-up (after WP1 is merged)

Diff each `config/*.php` against the Laravel 13 skeleton defaults and add keys that
the framework reads but we do not define (for example `app.maintenance`,
`session.encrypt`/`partitioned`, `logging.deprecations`, `database.*.url`,
`queue.failed`, `mail.*.scheme`), using skeleton defaults. Never rename an env variable
and never change an existing value. Update `CONTRIBUTING.md` repository layout table
(no kernels, `bootstrap/providers.php`, `routes/console.php`).

## Verification checklist (run from the worktree with the docker wrappers)

```
php artisan about
php artisan route:list            # 120 routes, actuator/* and api/* present, names unchanged
php artisan schedule:list         # 6 entries, same expressions
php artisan event:list            # InvitationCreated => SendInvitationMail
php artisan config:cache && php artisan config:clear
php artisan route:cache && php artisan route:clear
vendor/bin/phpunit                # all green
```

Compare `route:list --json` and `schedule:list` output before and after the change
(capture both from the base commit first).

## Result

- WP1 landed on `feat/laravel-skeleton`: bootstrap moved to `Application::configure()` (`ea1f5bba`, merged as `d68456c3`); kernels removed, providers in `bootstrap/providers.php`, schedule in `routes/console.php`.
- WP2 landed: keys the Laravel 13 skeleton defines and we lacked were added to the shared `config/*.php` files with skeleton defaults; no existing key, value, or env name was changed.
- Deliberately not added: mariadb/pgsql connections, `filesystems.disks.local.serve` (would register storage routes over our `storage/app` root), and `logging.channels.daily.max_files` (`days` still read, keeps retention at 7).
- Verified: `config:cache` and `config:clear` succeed, `about` runs, `php -l` clean on changed files, PHPUnit 332 tests green.
