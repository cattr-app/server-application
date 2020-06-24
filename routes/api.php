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

use Illuminate\Routing\Route as RouteModel;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

Route::macro('staticRoute', function ($prefix, Router $router) {
    Route::group([
        'prefix' => $prefix
    ], static function () use ($router) {
        $router->get('{screenshot}', 'ScreenshotController@screenshot');
        $router->get('thumbs/{screenshot}', 'ScreenshotController@thumbnail');
    });
});

// Static content processing
Route::group([
    'prefix' => 'uploads'
], static function (Router $router) {
    Route::staticRoute('screenshots', $router);
    Route::staticRoute('static', $router);
});

// Routes for login/register processing
Route::group([
    'middleware' => 'throttle:120,1',
    'prefix' => 'auth',
], static function (Router $router) {
    $router->post('login', 'AuthController@login');
    $router->post('logout', 'AuthController@logout');
    $router->post('logout-from-all', 'AuthController@logoutFromAll');
    $router->post('refresh', 'AuthController@refresh');
    $router->get('me', 'AuthController@me');
    $router->post('password/reset/request', 'PasswordResetController@request');
    $router->post('password/reset/validate', 'PasswordResetController@validate');
    $router->post('password/reset/process', 'PasswordResetController@process');

    $router->get('/register/{key}', 'RegistrationController@getForm');
    $router->post('/register/{key}', 'RegistrationController@postForm');
});

// Temporary fix for missing v1 prefix in the url
Route::group([
    'middleware' => 'throttle:120,1',
    'prefix' => 'v1/auth',
], static function (Router $router) {
    $router->post('login', 'AuthController@login');
    $router->post('logout', 'AuthController@logout');
    $router->post('logout-from-all', 'AuthController@logoutFromAll');
    $router->post('refresh', 'AuthController@refresh');
    $router->get('me', 'AuthController@me');
    $router->post('password/reset/request', 'PasswordResetController@request');
    $router->post('password/reset/validate', 'PasswordResetController@validate');
    $router->post('password/reset/process', 'PasswordResetController@process');

    $router->get('/register/{key}', 'RegistrationController@getForm');
    $router->post('/register/{key}', 'RegistrationController@postForm');
});

Route::group([
    'prefix' => 'status',
], static function (Router $router) {
    $router->get('/', 'StatusController@index');
});

// Main API routes
Route::group([
    'middleware' => ['auth:api', 'throttle:120,1'],
    'prefix' => 'v1',
], static function (Router $router) {
    //Invitations routes
    $router->get('/invitations/list', 'Api\v1\InvitationController@index');
    $router->get('/invitations/count', 'Api\v1\InvitationController@count');
    $router->post('/invitations/create', 'Api\v1\InvitationController@create');
    $router->post('/invitations/resend', 'Api\v1\InvitationController@resend');
    $router->post('/invitations/show', 'Api\v1\InvitationController@show');
    $router->post('/invitations/remove', 'Api\v1\InvitationController@destroy');

    //Projects routes
    $router->any('/projects/list', 'Api\v1\ProjectController@index');
    $router->any('/projects/count', 'Api\v1\ProjectController@count');
    $router->post('/projects/create', 'Api\v1\ProjectController@create');
    $router->post('/projects/edit', 'Api\v1\ProjectController@edit');
    $router->any('/projects/show', 'Api\v1\ProjectController@show');
    $router->post('/projects/remove', 'Api\v1\ProjectController@destroy');

    //Projects Users routes
    $router->any('/projects-users/list', 'Api\v1\ProjectsUsersController@index');
    $router->any('/projects-users/count', 'Api\v1\ProjectsUsersController@count');
    $router->post('/projects-users/create', 'Api\v1\ProjectsUsersController@create');
    $router->post('/projects-users/remove', 'Api\v1\ProjectsUsersController@destroy');
    $router->post('/projects-users/bulk-remove', 'Api\v1\ProjectsUsersController@bulkDestroy');

    //Tasks routes
    $router->any('/tasks/list', 'Api\v1\TaskController@index');
    $router->any('/tasks/count', 'Api\v1\TaskController@count');
    $router->any('/tasks/dashboard', 'Api\v1\TaskController@dashboard');
    $router->post('/tasks/create', 'Api\v1\TaskController@create');
    $router->post('/tasks/edit', 'Api\v1\TaskController@edit');
    $router->any('/tasks/show', 'Api\v1\TaskController@show');
    $router->post('/tasks/remove', 'Api\v1\TaskController@destroy');

    //Users routes
    $router->any('/users/list', 'Api\v1\UserController@index');
    $router->any('/users/count', 'Api\v1\UserController@count');
    $router->post('/users/create', 'Api\v1\UserController@create');
    $router->post('/users/edit', 'Api\v1\UserController@edit');
    $router->any('/users/show', 'Api\v1\UserController@show');
    $router->post('/users/remove', 'Api\v1\UserController@destroy');
    $router->post('/users/send-invite', 'Api\v1\UserController@sendInvite');

    //Screenshots routes
    $router->any('/screenshots/list', 'Api\v1\ScreenshotController@index');
    $router->any('/screenshots/count', 'Api\v1\ScreenshotController@count');
    $router->post('/screenshots/create', 'Api\v1\ScreenshotController@create');
    $router->post('/screenshots/edit', 'Api\v1\ScreenshotController@edit');
    $router->any('/screenshots/show', 'Api\v1\ScreenshotController@show');
    $router->post('/screenshots/remove', 'Api\v1\ScreenshotController@destroy');

    //Time Intervals routes
    $router->any('/time-intervals/list', 'Api\v1\TimeIntervalController@index');
    $router->any('/time-intervals/count', 'Api\v1\TimeIntervalController@count');
    $router->post('/time-intervals/create', 'Api\v1\TimeIntervalController@create');
    $router->post('/time-intervals/edit', 'Api\v1\TimeIntervalController@edit');
    $router->post('/time-intervals/bulk-edit', 'Api\v1\TimeIntervalController@bulkEdit');
    $router->any('/time-intervals/show', 'Api\v1\TimeIntervalController@show');
    $router->post('/time-intervals/remove', 'Api\v1\TimeIntervalController@destroy');
    $router->post('/time-intervals/bulk-remove', 'Api\v1\TimeIntervalController@bulkDestroy');

    $router->any('/time-intervals/dashboard', 'Api\v1\Statistic\DashboardController@timeIntervals');
    $router->any('/time-intervals/day-duration', 'Api\v1\Statistic\DashboardController@timePerDay');

    //Time routes
    $router->any('/time/total', 'Api\v1\TimeController@total');
    $router->any('/time/tasks', 'Api\v1\TimeController@tasks');

    //Role routes
    $router->any('/roles/list', 'Api\v1\RolesController@index');
    $router->any('/roles/count', 'Api\v1\RolesController@count');
    $router->post('/roles/create', 'Api\v1\RolesController@create');
    $router->post('/roles/edit', 'Api\v1\RolesController@edit');
    $router->any('/roles/show', 'Api\v1\RolesController@show');
    $router->post('/roles/remove', 'Api\v1\RolesController@destroy');
    $router->any('/roles/allowed-rules', 'Api\v1\RolesController@allowedRules');
    $router->any('/roles/project-rules', 'Api\v1\RolesController@projectRules');
    $router->post('/roles/attach-user', 'Api\v1\RolesController@attachToUser');
    $router->post('/roles/detach-user', 'Api\v1\RolesController@detachFromUser');

    //Rule routes
    $router->any('/rules/list', 'Api\v1\RulesController@index');
    $router->any('/rules/count', 'Api\v1\RulesController@count');
    $router->post('/rules/edit', 'Api\v1\RulesController@edit');
    $router->any('/rules/actions', 'Api\v1\RulesController@actions');

    // Statistic routes
    $router->any('/project-report/list', 'Api\v1\Statistic\ProjectReportController@report');
    $router->any('/project-report/list/tasks/{id}', 'Api\v1\Statistic\ProjectReportController@task');
    $router->any('/time-use-report/list', 'Api\v1\Statistic\TimeUseReportController@report');
    $router->any('/project-report/screenshots', 'Api\v1\Statistic\ProjectReportController@screenshots');

    // About
    $router->get('/about', 'Api\v1\AboutController');

    //Company settings
    $router->get('/company-settings', 'Api\v1\CompanySettingsController@index');
    $router->patch('/company-settings', 'Api\v1\CompanySettingsController@update');
});

Route::any('(.*)', 'Controller@universalRoute');
