<?php

/**
 * Lists the routes that no feature test calls.
 *
 * Runs the Feature suite with ROUTE_COVERAGE_LOG set, so that tests/TestCase.php records every
 * matched route, and prints the registered routes missing from that log as a Markdown checklist.
 * Tests that opt out of recording (the GET route smoke test) do not count.
 *
 *   php scripts/route-coverage.php                    # run the Feature suite, then report
 *   php scripts/route-coverage.php --log=routes.log   # report from the log of an earlier run
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

$root = dirname(__DIR__);

$options = getopt('', ['log:']);
$log = $options['log'] ?? null;

if ($log === null) {
    $log = tempnam(sys_get_temp_dir(), 'route-coverage-');

    $phpunit = escapeshellarg($root . '/vendor/bin/phpunit');
    passthru(
        'ROUTE_COVERAGE_LOG=' . escapeshellarg($log) . " $phpunit --no-progress --testsuite Feature",
        $status
    );

    if ($status !== 0) {
        fwrite(STDERR, "PHPUnit exited with status $status, the report may miss routes of failed tests\n");
    }
}

if (!is_readable($log)) {
    fwrite(STDERR, "Cannot read $log\n");
    exit(1);
}

$covered = array_flip(array_filter(array_map('trim', file($log))));

if (!isset($options['log'])) {
    unlink($log);
}

require $root . '/vendor/autoload.php';
$app = require $root . '/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$routes = collect($app['router']->getRoutes()->getRoutes())
    ->reject(static fn(Route $route) => $route->isFallback || $route->getName() === 'universal_route');

$uncovered = $routes
    ->reject(static fn(Route $route) => isset($covered[implode('|', $route->methods()) . ' ' . $route->uri()]))
    ->sortBy(static fn(Route $route) => $route->uri())
    ->map(static fn(Route $route) => sprintf(
        '- [ ] `%s %s` (%s)',
        $route->methods() === Router::$verbs ? 'ANY' : implode('|', array_diff($route->methods(), ['HEAD'])),
        $route->uri(),
        ltrim(str_replace('App\\Http\\Controllers\\', '', $route->getActionName()), '\\'),
    ))
    ->values();

echo "Routes without a feature test: {$uncovered->count()} of {$routes->count()}" . PHP_EOL . PHP_EOL;
echo $uncovered->implode(PHP_EOL) . PHP_EOL;
