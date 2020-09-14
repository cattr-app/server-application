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

use Illuminate\Routing\Router;

// Static content processing
Route::group([
    'prefix' => 'uploads'
], static function (Router $router) {
    $router->get('screenshots/{screenshot}', 'ScreenshotController@screenshot');
    $router->get('screenshots/thumbs/{screenshot}', 'ScreenshotController@thumbnail');
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

    $router->get('register/{key}', 'RegistrationController@getForm');
    $router->post('register/{key}', 'RegistrationController@postForm');
});

Route::get('status', 'StatusController@index');

// Main API routes
Route::group([
    'middleware' => ['auth:api', 'throttle:120,1'],
], static function (Router $router) {
    //Invitations routes
    $router->any('invitations/list', 'Api\InvitationController@index');
    $router->get('invitations/count', 'Api\InvitationController@count');
    $router->post('invitations/create', 'Api\InvitationController@create');
    $router->post('invitations/resend', 'Api\InvitationController@resend');
    $router->post('invitations/show', 'Api\InvitationController@show');
    $router->post('invitations/remove', 'Api\InvitationController@destroy');

    //Projects routes
    $router->any('projects/list', 'Api\ProjectController@index');
    $router->any('projects/count', 'Api\ProjectController@count');
    $router->post('projects/create', 'Api\ProjectController@create');
    $router->post('projects/edit', 'Api\ProjectController@edit');
    $router->any('projects/show', 'Api\ProjectController@show');
    $router->post('projects/remove', 'Api\ProjectController@destroy');

    //Projects Users routes
    $router->any('projects-users/list', 'Api\ProjectsUsersController@index');
    $router->any('projects-users/count', 'Api\ProjectsUsersController@count');
    $router->post('projects-users/create', 'Api\ProjectsUsersController@create');
    $router->post('projects-users/remove', 'Api\ProjectsUsersController@destroy');
    $router->post('projects-users/bulk-remove', 'Api\ProjectsUsersController@bulkDestroy');

    //Tasks routes
    $router->any('tasks/list', 'Api\TaskController@index');
    $router->any('tasks/count', 'Api\TaskController@count');
    $router->any('tasks/dashboard', 'Api\TaskController@dashboard');
    $router->post('tasks/create', 'Api\TaskController@create');
    $router->post('tasks/edit', 'Api\TaskController@edit');
    $router->any('tasks/show', 'Api\TaskController@show');
    $router->post('tasks/remove', 'Api\TaskController@destroy');

    //Users routes
    $router->any('users/list', 'Api\UserController@index');
    $router->any('users/count', 'Api\UserController@count');
    $router->post('users/create', 'Api\UserController@create');
    $router->post('users/edit', 'Api\UserController@edit');
    $router->any('users/show', 'Api\UserController@show');
    $router->post('users/remove', 'Api\UserController@destroy');
    $router->post('users/send-invite', 'Api\UserController@sendInvite');
    $router->patch('users/activity', 'Api\UserController@updateActivity');

    //Screenshots routes
    $router->any('screenshots/list', 'Api\ScreenshotController@index');
    $router->any('screenshots/count', 'Api\ScreenshotController@count');
    $router->post('screenshots/create', 'Api\ScreenshotController@create');
    $router->post('screenshots/edit', 'Api\ScreenshotController@edit');
    $router->any('screenshots/show', 'Api\ScreenshotController@show');
    $router->post('screenshots/remove', 'Api\ScreenshotController@destroy');

    //Time Intervals routes
    $router->any('time-intervals/list', 'Api\TimeIntervalController@index');
    $router->any('time-intervals/count', 'Api\TimeIntervalController@count');
    $router->post('time-intervals/create', 'Api\TimeIntervalController@create');
    $router->post('time-intervals/edit', 'Api\TimeIntervalController@edit');
    $router->post('time-intervals/bulk-edit', 'Api\TimeIntervalController@bulkEdit');
    $router->any('time-intervals/show', 'Api\TimeIntervalController@show');
    $router->post('time-intervals/remove', 'Api\TimeIntervalController@destroy');
    $router->post('time-intervals/bulk-remove', 'Api\TimeIntervalController@bulkDestroy');

    $router->any('time-intervals/dashboard', 'Api\Statistic\DashboardController@timeIntervals');
    $router->any('time-intervals/day-duration', 'Api\Statistic\DashboardController@timePerDay');

    //Time routes
    $router->any('time/total', 'Api\TimeController@total');
    $router->any('time/tasks', 'Api\TimeController@tasks');

    //Role routes
    $router->any('roles/list', 'Api\RolesController@index');
    $router->any('roles/count', 'Api\RolesController@count');
    $router->post('roles/create', 'Api\RolesController@create');
    $router->post('roles/edit', 'Api\RolesController@edit');
    $router->any('roles/show', 'Api\RolesController@show');
    $router->post('roles/remove', 'Api\RolesController@destroy');
    $router->any('roles/allowed-rules', 'Api\RolesController@allowedRules');
    $router->any('roles/project-rules', 'Api\RolesController@projectRules');
    $router->post('roles/attach-user', 'Api\RolesController@attachToUser');
    $router->post('roles/detach-user', 'Api\RolesController@detachFromUser');

    //Rule routes
    $router->any('rules/list', 'Api\RulesController@index');
    $router->any('rules/count', 'Api\RulesController@count');
    $router->post('rules/edit', 'Api\RulesController@edit');
    $router->any('rules/actions', 'Api\RulesController@actions');

    // Statistic routes
    $router->any('project-report/list', 'Api\Statistic\ProjectReportController@report');
    $router->any('project-report/list/tasks/{id}', 'Api\Statistic\ProjectReportController@task');
    $router->any('time-use-report/list', 'Api\Statistic\TimeUseReportController@report');
    $router->any('project-report/screenshots', 'Api\Statistic\ProjectReportController@screenshots');

    // About
    $router->get('about', 'Api\AboutController');

    //Company settings
    $router->get('company-settings', 'Api\CompanySettingsController@index');
    $router->patch('company-settings', 'Api\CompanySettingsController@update');
});

Route::any('(.*)', 'Controller@universalRoute');
