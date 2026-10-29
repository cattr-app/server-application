# Browser tests (Playwright)

End-to-end harness of the web application: `smoke`, `journeys`, `visual` and the lazy module check
`module-stub`. Configuration: `playwright.config.js`; tests and helpers: `tests/e2e/`.

Browsers are **not** installed on developer machines. Tests run only inside
`mcr.microsoft.com/playwright:v<version of @playwright/test>-noble` (currently `v1.64.1-noble`), with
`--network host`; the config refuses to start elsewhere (`E2E_ALLOW_HOST=1` overrides that, never for
baselines). CI uses the same image, so screenshots match.

## Layout

| Path                                               | What                                                                                                                                                       |
| -------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `auth.setup.js`                                    | project `setup`: logs in once through the UI, saves `tests/e2e/.auth/admin.json` (storageState)                                                            |
| `smoke/pages.spec.js`                              | project `smoke`: every page of the checklist opens and shows its key element                                                                               |
| `journeys/*.spec.js`                               | project `journeys`: login/logout, grids (filter, sorting, pagination), task create/edit, kanban drag, manual time, language switch, reports and navigation |
| `visual/pages.spec.js`                             | projects `visual-desktop` (1440×900) and `visual-mobile` (390×844): `toHaveScreenshot` of the pages with `visual: true`                                    |
| `__screenshots__/<project>/`                       | committed visual baseline (taken on Vue 2, `main@bfafed73`)                                                                                                |
| `module-stub/`                                     | project `module-stub` (only with `E2E_MODULE_STUB` set) and `build.sh`                                                                                     |
| `fixtures/vendor_modules/Test/Stub/module.init.js` | external frontend module used by `module-stub`                                                                                                             |
| `support/pages.js`                                 | the page checklist (route, key element, visual flag, masks, known breakage)                                                                                |
| `support/test.js`                                  | base `test`: fixed language/clock/network, console guard                                                                                                   |
| `support/seed.php`                                 | deterministic seed (see below)                                                                                                                             |
| `console-allowlist.js`                             | console messages the guard tolerates, with the reason for each                                                                                             |

## Stand

The suites expect a seeded backend at `E2E_BASE_URL` (default `http://127.0.0.1:8001`) serving built
assets ("stand C" of the migration plan: `pnpm dev` or `pnpm prod` + `php artisan serve`, MySQL 8.4).

Stand settings that differ from production, and why:

| Setting                          | Why                                                                                                                                    |
| -------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------- |
| `QUEUE_CONNECTION=sync`          | no queue worker: screenshot thumbnails and task-assignee jobs run inline                                                               |
| `BROADCAST_DRIVER=log`           | no Reverb: the reverb broadcaster appends a 500 ("Unable to parse URI") to API responses                                               |
| `CACHE_DRIVER=array`             | the API is throttled to 120 requests/min per user; `artisan serve` boots the app per request, so the array store disables the throttle |
| `RECAPTCHA_ENABLED=false`        | no captcha on the login form                                                                                                           |
| `--no-reload` on `artisan serve` | passes the process environment (module flags) to the PHP server                                                                        |

Seed: `php artisan migrate --force && php tests/e2e/support/seed.php`. The script runs the regular
`cattr:reset --force --seed` (InitialSeeder + AdminSeeder + DemoDataSeeder; login `admin@cattr.app` /
`password`) with the clock frozen at `2026-03-11 12:00:00 UTC` and a seeded Faker, then replaces
values the factories draw from `RAND()`/`random_int()`, so every reseed yields identical data.

Local helper (not in git): `/home/caelan/cattr/.local/e2e/stand.sh`; names and ports come from
`E2E_PREFIX`, `E2E_DB_PORT`, `E2E_DB_NAME`, `E2E_APP_PORT`:

```sh
cd <worktree>
export E2E_PREFIX=cattr-mig-p04 E2E_DB_PORT=3310 E2E_DB_NAME=cattr_p04 E2E_APP_PORT=8001
pnpm install --frozen-lockfile
pnpm prod                                          # build first: `up` waits for HTTP 200 on `/`, which needs the build
/home/caelan/cattr/.local/e2e/stand.sh up          # MySQL container, .env, migrate + seed, artisan serve
# later rebuilds (e.g. `pnpm dev` for the [Vue warn] check) need no restart of the stand
/home/caelan/cattr/.local/e2e/stand.sh run test:e2e
/home/caelan/cattr/.local/e2e/stand.sh down        # removes the MySQL and backend containers
```

Without the helper, the Playwright part is:

```sh
docker run --rm --network host --ipc=host --init --user "$(id -u):$(id -g)" -e HOME=/tmp \
  -e E2E_BASE_URL=http://127.0.0.1:8001 -v "$PWD:$PWD" -w "$PWD" \
  mcr.microsoft.com/playwright:v1.64.1-noble npm run test:e2e
```

## Suites

- `pnpm test:e2e` (inside the image) — `setup`, `smoke`, `visual-desktop`, `visual-mobile`, `journeys`,
  in that order, one worker (the projects share one database).
- `--project=smoke` with a **development** build (`pnpm dev`): only that build prints `[Vue warn]`;
  pass `E2E_BUILD=dev` for clarity of the report. CI runs smoke on both builds.
- Journeys that change data use their own entities (created through the API, removed in `finally` or `afterEach`) or
  restore what they change (language), so they do not depend on order and leave the seed intact.
  Logout runs in its own session, so the shared token stays valid.
- Determinism: cookie `lang=en`, locale `en-US`, timezone `UTC`, `page.clock.install()` at the seed time
  (time keeps flowing, so lodash throttle/debounce work), service workers blocked, and every request
  outside the stand is answered locally (Gravatar → 404, Google Fonts → empty, others aborted), so the
  system fonts of the image are used.

### Module flag (`module-stub`)

Checks that an external frontend module is fetched as a separate chunk only when
`MODULE_FRONTEND_TEST_STUB_ENABLED=true`:

```sh
bash tests/e2e/module-stub/build.sh            # pnpm prod with the fixture copied in, removed on exit
# backend without the flag:
E2E_MODULE_STUB=disabled <playwright> test --project=module-stub
# backend restarted with MODULE_FRONTEND_TEST_STUB_ENABLED=true:
E2E_MODULE_STUB=enabled <playwright> test --project=module-stub
pnpm prod                                      # back to a regular build
```

`stand.sh module-stub` does all of it. The fixture uses no framework API, so it survives the migration
unchanged (only `build.sh` may need the new build command).

## Visual baseline

Masked: avatars (`.avatar__photo`, `.vue-avatar--wrapper`), online status, screenshot images, every
`<canvas>` (chart.js/echarts), app version on About, and the company settings menu (its item order
changes between loads on `main@bfafed73`). CSS animations, transitions, caret and scrollbars are off.

Update **only inside the Playwright image** and review every changed PNG:

```sh
/home/caelan/cattr/.local/e2e/stand.sh run test:e2e:update     # = playwright test --project=visual-* --update-snapshots=changed
git diff --stat tests/e2e/__screenshots__
```

During the migration a diff is either fixed or accepted with a reason in the PR, then the baseline is
updated in the same PR.

## Console allowlist

The console guard (`support/test.js`) fails a test on any console error or warning, page error and
`[Vue warn]`/`[Vue tip]` that no rule in `console-allowlist.js` matches. A test can allow a message it
provokes on purpose with `consoleGuard.expect(/regexp/)` (e.g. the 401 of a wrong password).

- `STAND_RULES` — properties of this stand: websocket connection errors (no Reverb; disable the rule with
  `E2E_REVERB=1` on a stand with Reverb) and the blocked service worker.
- `BY_DESIGN` — Gravatar `d=404` probe of the avatar, cancelled requests on navigation.
- `KNOWN_VUE2` — warnings and errors present on Vue 2 (`main@bfafed73`). They must go away in the
  migration; remove the rule together with the fix.

Pages broken on `main@bfafed73` are marked `knownBroken` in `support/pages.js`; their smoke test is
expected to fail and reports "unexpectedly passed" once fixed — then drop the mark.

To take an inventory of console messages instead of failing (e.g. after a big change), run with
`E2E_CONSOLE_COLLECT=1`: unexpected messages go to `test-results/console-collected.jsonl`.
