<?php

/**
 * Route::resource() actions:
 * Method    Path                      Action    Route Name
 * GET       /{controller}             index     {controller}.index
 * GET       /{controller}/create      create    {controller}.create
 * POST      /{controller}             store     {controller}.store
 * GET       /{controller}/{id}        show      {controller}.show
 * GET       /{controller}/{id}/edit   edit      {controller}.edit
 * PUT       /{controller}/{id}        update    {controller}.update
 * DELETE    /{controller}/{id}        destroy   {controller}.destroy
 *
 * Use only target methods:
 * Route::resource('{controller}', 'ControllerClass', [
 *      'only' => ['index', 'show']
 * ]);
 *
 * Use all methods except target
 * Route::resource('{controller}', 'ControllerClass', [
 *      'except' => ['edit', 'create']
 * ]);
 */

use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Routing\Route as RouteModel;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

// Static content processing
Route::group([
    'prefix' => 'uploads'
], static function (Router $router) {
    $router->group([
        'prefix' => 'screenshots'
    ], static function (Router $router) {
        $router->get('{screenshot}', 'ScreenshotController@screenshot');
        $router->get('thumbs/{screenshot}', 'ScreenshotController@thumbnail');
    });
});

// Routes for login/register processing
Route::group([
    'prefix' => 'auth',
], static function (Router $router) {
    $router->any('ping', 'AuthController@ping');
    $router->any('check', 'AuthController@check');
    $router->post('login', 'AuthController@login');
    $router->any('logout', 'AuthController@logout');
    $router->any('logout-all', 'AuthController@logoutAll');
    $router->post('refresh', 'AuthController@refresh');
    $router->any('me', 'AuthController@me');
    $router->post('send-reset', 'AuthController@sendReset');
    $router->post('confirm-reset', 'AuthController@getReset')->name('password.reset');
    $router->post('reset', 'AuthController@reset');

    $router->get('/register/{key}', 'RegistrationController@getForm');
    $router->post('/register/{key}', 'RegistrationController@postForm');
});


// Main API routes
Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'v1',
], static function (Router $router) {
    // Register routes
    $router->post('/register/create', 'RegistrationController@create');

    //Projects routes
    $router->any('/projects/list', 'Api\v1\ProjectController@index');
    $router->any('/projects/count', 'Api\v1\ProjectController@count');
    $router->post('/projects/create', 'Api\v1\ProjectController@create');
    $router->post('/projects/edit', 'Api\v1\ProjectController@edit');
    $router->any('/projects/show', 'Api\v1\ProjectController@show');
    $router->post('/projects/remove', 'Api\v1\ProjectController@destroy');
    $router->any('/projects/tasks', 'Api\v1\ProjectController@tasks');

    //Projects Users routes
    $router->any('/projects-users/list', 'Api\v1\ProjectsUsersController@index');
    $router->any('/projects-users/count', 'Api\v1\ProjectsUsersController@count');
    $router->post('/projects-users/create', 'Api\v1\ProjectsUsersController@create');
    $router->post('/projects-users/bulk-create', 'Api\v1\ProjectsUsersController@bulkCreate');
    $router->post('/projects-users/remove', 'Api\v1\ProjectsUsersController@destroy');
    $router->post('/projects-users/bulk-remove', 'Api\v1\ProjectsUsersController@bulkDestroy');

    //Projects Roles routes
    $router->any('/projects-roles/list', 'Api\v1\ProjectsRolesController@index');
    $router->any('/projects-roles/count', 'Api\v1\ProjectsRolesController@count');
    $router->post('/projects-roles/create', 'Api\v1\ProjectsRolesController@create');
    $router->post('/projects-roles/bulk-create', 'Api\v1\ProjectsRolesController@bulkCreate');
    $router->post('/projects-roles/remove', 'Api\v1\ProjectsRolesController@destroy');
    $router->post('/projects-roles/bulk-remove', 'Api\v1\ProjectsRolesController@bulkDestroy');

    //Tasks routes
    $router->any('/tasks/list', 'Api\v1\TaskController@index');
    $router->any('/tasks/count', 'Api\v1\TaskController@count');
    $router->any('/tasks/dashboard', 'Api\v1\TaskController@dashboard');
    $router->post('/tasks/create', 'Api\v1\TaskController@create');
    $router->post('/tasks/edit', 'Api\v1\TaskController@edit');
    $router->any('/tasks/show', 'Api\v1\TaskController@show');
    $router->post('/tasks/remove', 'Api\v1\TaskController@destroy');
    $router->any('/tasks/activity', 'Api\v1\TaskController@activity');

    $router->any('/task-comment/list', 'Api\v1\TaskCommentController@index');
    $router->post('/task-comment/create', 'Api\v1\TaskCommentController@create');
    $router->any('/task-comment/show', 'Api\v1\TaskCommentController@show');
    $router->post('/task-comment/remove', 'Api\v1\TaskCommentController@destroy');

    //Users routes
    $router->any('/users/list', 'Api\v1\UserController@index');
    $router->any('/users/count', 'Api\v1\UserController@count');
    $router->post('/users/create', 'Api\v1\UserController@create');
    $router->post('/users/edit', 'Api\v1\UserController@edit');
    $router->any('/users/show', 'Api\v1\UserController@show');
    $router->post('/users/remove', 'Api\v1\UserController@destroy');
    $router->post('/users/bulk-edit', 'Api\v1\UserController@bulkEdit');
    $router->any('/users/relations', 'Api\v1\UserController@relations');

    //Screenshots routes
    $router->any('/screenshots/list', 'Api\v1\ScreenshotController@index');
    $router->any('/screenshots/count', 'Api\v1\ScreenshotController@count');
    $router->any('/screenshots/dashboard', 'Api\v1\ScreenshotController@dashboard');
    $router->post('/screenshots/create', 'Api\v1\ScreenshotController@create');
    $router->post('/screenshots/bulk-create', 'Api\v1\ScreenshotController@bulkCreate');
    $router->post('/screenshots/edit', 'Api\v1\ScreenshotController@edit');
    $router->any('/screenshots/show', 'Api\v1\ScreenshotController@show');
    $router->post('/screenshots/remove', 'Api\v1\ScreenshotController@destroy');

    //Time Intervals routes
    $router->any('/time-intervals/list', 'Api\v1\TimeIntervalController@index');
    $router->any('/time-intervals/count', 'Api\v1\TimeIntervalController@count');
    $router->post('/time-intervals/create', 'Api\v1\TimeIntervalController@create');
    $router->post('/time-intervals/bulk-create', 'Api\v1\TimeIntervalController@bulkCreate');
    $router->post('/time-intervals/edit', 'Api\v1\TimeIntervalController@edit');
    $router->any('/time-intervals/show', 'Api\v1\TimeIntervalController@show');
    $router->post('/time-intervals/remove', 'Api\v1\TimeIntervalController@destroy');
    $router->any('/time-intervals/dashboard', 'Api\v1\Statistic\DashboardController@timeIntervals');
    $router->post('/time-intervals/bulk-remove', 'Api\v1\TimeIntervalController@bulkDestroy');
    $router->post('/time-intervals/manual-create', 'Api\v1\TimeIntervalController@manualCreate');

    //Time routes
    $router->any('/time/total', 'Api\v1\TimeController@total');
    $router->any('/time/project', 'Api\v1\TimeController@project');
    $router->any('/time/tasks', 'Api\v1\TimeController@tasks');
    $router->any('/time/task', 'Api\v1\TimeController@task');
    $router->any('/time/task-user', 'Api\v1\TimeController@taskUser');

    //Role routes
    $router->any('/roles/list', 'Api\v1\RolesController@index');
    $router->any('/roles/count', 'Api\v1\RolesController@count');
    $router->post('/roles/create', 'Api\v1\RolesController@create');
    $router->post('/roles/edit', 'Api\v1\RolesController@edit');
    $router->any('/roles/show', 'Api\v1\RolesController@show');
    $router->post('/roles/remove', 'Api\v1\RolesController@destroy');
    $router->any('/roles/allowed-rules', 'Api\v1\RolesController@allowedRules');
    $router->post('/roles/attach-user', 'Api\v1\RolesController@attachToUser');
    $router->post('/roles/detach-user', 'Api\v1\RolesController@detachFromUser');

    //Rule routes
    $router->any('/rules/list', 'Api\v1\RulesController@index');
    $router->any('/rules/count', 'Api\v1\RulesController@count');
    $router->post('/rules/edit', 'Api\v1\RulesController@edit');
    $router->post('/rules/bulk-edit', 'Api\v1\RulesController@bulkEdit');
    $router->any('/rules/actions', 'Api\v1\RulesController@actions');


    // Statistic routes
    $router->post('/project-report/list', 'Api\v1\Statistic\ProjectReportController@report');
    $router->post('/project-report/projects', 'Api\v1\Statistic\ProjectReportController@projects');
    $router->post('/project-report/list/tasks/{id}', 'Api\v1\Statistic\ProjectReportController@task');
    $router->post('/time-duration/list', 'Api\v1\Statistic\ProjectReportController@days');
    $router->post('/time-use-report/list', 'Api\v1\Statistic\TimeUseReportController@report');
});

// Laravel router pass to fallback not non-exist urls only but wrong-method requests too.
// So required to check if route have alternative request methods
// and throw not-found or wrong-method exceptions manually
Route::fallback(function () {
    /** @var Router $this */
    /** @var Request $request */
    $request = $this->currentRequest;
    /** @var RouteCollection $routes */
    $routeCollection = $this->routes;
    /** @var string[] $methods */
    $methods = array_diff(Router::$verbs, [ $request->getMethod(), 'OPTIONS' ]);

    foreach ($methods as $method) {
        // Get all routes for method without fallback routes
        /** @var Route[]|Collection $routes */
        $routes = collect($routeCollection->get($method))->filter(static function ($route) {
            /** @var RouteModel $route */
            return !$route->isFallback && $route->uri !== '{fallbackPlaceholder}';
        });

        // Look if any route have match with current request
        $mismatch = $routes->first(static function ($value) use ($request) {
            /** @var RouteModel $value */
            return $value->matches($request, false );
        });

        // Throw wrong-method exception if matches found
        if ($mismatch !== null) {
            throw new MethodNotAllowedHttpException([]);
        }
    }

    // No matches, throw not-found exception
    throw new NotFoundHttpException();
});
