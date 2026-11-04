<?php

namespace App\Docs\OpenApi;

use Illuminate\Routing\Route;

/**
 * Picks the one HTTP method to document for `Route::any()` routes (Scramble would take the first one, GET).
 *
 * The method comes from the explicit route name => method table below. Read-only endpoints that take
 * a large filter in the request body (`list`, `show`) are documented as POST, the rest as GET.
 * A new `Route::any()` route must be added here: tests/Unit/AnyRouteMethodTest.php fails until it is.
 * Unmapped routes are documented as GET. Routes with an explicit method list are left as they are.
 */
class AnyRouteMethod
{
    /** Route name => documented HTTP method for every `Route::any()` route. */
    public const METHODS = [
        'intervals.count' => 'get',
        'intervals.list' => 'post',
        'intervals.show' => 'post',
        'invitations.list' => 'get',
        'priorities.list' => 'get',
        'priorities.show' => 'post',
        'project_groups.count' => 'get',
        'project_groups.list' => 'get',
        'project_groups.show' => 'get',
        'projects.count' => 'get',
        'projects.list' => 'get',
        'projects.show' => 'get',
        'projects_members.list' => 'post',
        'roles.list' => 'post',
        'statuses.list' => 'get',
        'statuses.show' => 'post',
        'task_comments.list' => 'get',
        'task_comments.show' => 'post',
        'tasks.count' => 'get',
        'tasks.list' => 'post',
        'tasks.show' => 'post',
        'time.tasks' => 'get',
        'time.total' => 'get',
        'users.count' => 'get',
        'users.list' => 'get',
        'users.show' => 'get',
    ];

    public const DEFAULT_METHOD = 'get';

    public function __invoke(Route $route): string
    {
        $methods = array_map('strtolower', $route->methods());

        if (!self::isAny($route)) {
            return $methods[0];
        }

        return self::METHODS[$route->getName()] ?? self::DEFAULT_METHOD;
    }

    /** Whether the route was registered with `Route::any()`. */
    public static function isAny(Route $route): bool
    {
        $methods = $route->methods();

        return in_array('POST', $methods, true) && in_array('GET', $methods, true) && in_array('PUT', $methods, true);
    }
}
