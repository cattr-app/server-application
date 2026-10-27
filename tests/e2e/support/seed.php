<?php

/**
 * Deterministic seed for the Playwright stand (tests/e2e).
 *
 * Runs the regular `cattr:reset --force --seed` (InitialSeeder + AdminSeeder + DemoDataSeeder),
 * but with a frozen clock and a seeded Faker, so that names, dates and intervals are identical
 * on every reseed (the visual baseline depends on them). Values the factories draw from MySQL RAND()
 * or random_int() are normalised afterwards. Only test data is touched; no application code.
 *
 * Usage (from the repository root, inside the PHP image): php tests/e2e/support/seed.php
 * Keep SEED_NOW in sync with FROZEN_NOW in tests/e2e/support/constants.js.
 */

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

const SEED_NOW = '2026-03-11 12:00:00';
const FAKER_SEED = 20260311;

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

Carbon::setTestNow(Carbon::parse(SEED_NOW, 'UTC'));
CarbonImmutable::setTestNow(CarbonImmutable::parse(SEED_NOW, 'UTC'));
$app->make(Faker\Generator::class)->seed(FAKER_SEED);

// Workaround for a bug in the application on main@bfafed73 (not fixed here, see tests/e2e/README.md):
// cattr:reset truncates `statuses`, then StatusSeeder inserts Open and Closed without `order`, and
// both get order = 0 -> "Duplicate entry '0' for key 'statuses.statuses_order_unique'".
// Give them the order the migration 2023_12_27_123901 assigns (order = id).
App\Models\Status::creating(static function (App\Models\Status $status) {
    if (!$status->order && $status->id) {
        $status->order = $status->id;
    }
});

// cattr:reset keeps the `jobs` table, and GenerateScreenshotThumbnail is ShouldBeUnique (lock in the
// cache for an hour): drop jobs and locks of a previous seed, or the new thumbnails are never generated.
DB::table('jobs')->delete();
$kernel->call('cache:clear');

try {
    $status = $kernel->call('cattr:reset', ['--force' => true, '--seed' => true]);
} catch (Symfony\Component\Console\Exception\CommandNotFoundException $e) {
    // Second bug on main@bfafed73: ResetCommand imports Nwidart\Modules\Commands\SeedCommand, which
    // nwidart/laravel-modules 13 moved to Commands\Database\SeedCommand. The module seed is the last
    // step of cattr:reset and the stand has no backend modules, so only this exact failure is tolerated.
    if ($e->getMessage() !== 'Command "Nwidart\Modules\Commands\SeedCommand" is not defined.') {
        throw $e;
    }
    fwrite(STDERR, "warning: cattr:reset could not run the module seeder (known bug, ignored): {$e->getMessage()}\n");
    $status = 0;
}
echo $kernel->output();
if ($status !== 0) {
    exit($status);
}

// Thumbnails and task assignees are queued jobs. The stand runs QUEUE_CONNECTION=sync; with another
// connection (e.g. the default `database`) process them now, still with the frozen clock.
if (config('queue.default') !== 'sync') {
    $kernel->call('queue:work', ['--stop-when-empty' => true, '--tries' => 1]);
}
// GenerateScreenshotThumbnail is ShouldBeUnique: under the frozen clock its cache locks never expire,
// and repeated dispatches for the same interval ids are dropped. Create missing thumbnails directly.
$screenshots = $app->make(App\Contracts\ScreenshotService::class);
foreach (DB::table('time_intervals')->orderBy('id')->pluck('id') as $id) {
    if (Storage::exists($screenshots->getScreenshotPath($id)) && !Storage::exists($screenshots->getThumbPath($id))) {
        $screenshots->createThumbnail($id);
    }
}

// MySQL RAND() / random_int() in the factories: replace them with values derived from ids.
$priorities = DB::table('priorities')->orderBy('id')->pluck('id')->all();
$statuses = DB::table('statuses')->orderBy('id')->pluck('id')->all();
DB::table('projects')->update(['default_priority_id' => $priorities[0]]);
foreach (DB::table('tasks')->orderBy('id')->pluck('id') as $i => $id) {
    DB::table('tasks')->where('id', $id)->update([
        'priority_id' => $priorities[$i % count($priorities)],
        // Most tasks stay open (kanban and grids need them), every fourth one is closed.
        'status_id' => $i % 4 === 3 ? $statuses[1] : $statuses[0],
    ]);
}
foreach (DB::table('users')->orderBy('id')->pluck('id') as $i => $id) {
    DB::table('users')->where('id', $id)->update([
        'last_activity' => Carbon::now()->subMinutes(10 + $i)->toDateTimeString(),
    ]);
}

$admin = DB::table('users')->where('email', 'admin@cattr.app')->first();
if (!$admin || !password_verify('password', $admin->password)) {
    fwrite(STDERR, "admin@cattr.app / password was not created\n");
    exit(1);
}
printf(
    "Seeded at %s: %d users, %d projects, %d tasks, %d intervals; admin id %d\n",
    SEED_NOW,
    DB::table('users')->count(),
    DB::table('projects')->count(),
    DB::table('tasks')->count(),
    DB::table('time_intervals')->count(),
    $admin->id,
);
