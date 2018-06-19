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
use Illuminate\Routing\Router;

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
], function(Router $router) {
    $router->any('ping', 'AuthController@ping');
    $router->post('login', 'AuthController@login');
    $router->any('logout', 'AuthController@logout');
    $router->post('refresh', 'AuthController@refresh');
    $router->any('me', 'AuthController@me');
});

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'v1',
], function (Router $router) {
    //Projects routes
    $router->any('/projects/list', 'Api\v1\ProjectController@index');
    $router->post('/projects/create', 'Api\v1\ProjectController@create');
    $router->post('/projects/edit', 'Api\v1\ProjectController@edit');
    $router->any('/projects/show', 'Api\v1\ProjectController@show');
    $router->post('/projects/remove', 'Api\v1\ProjectController@destroy');
    $router->any('/projects/relations', 'Api\v1\ProjectController@relations');

    //Tasks routes
    $router->any('/tasks/list', 'Api\v1\TaskController@index');
    $router->any('/tasks/dashboard', 'Api\v1\TaskController@dashboard');
    $router->post('/tasks/create', 'Api\v1\TaskController@create');
    $router->post('/tasks/edit', 'Api\v1\TaskController@edit');
    $router->any('/tasks/show', 'Api\v1\TaskController@show');
    $router->post('/tasks/remove', 'Api\v1\TaskController@destroy');

    //Users routes
    $router->any('/users/list', 'Api\v1\UserController@index');
    $router->post('/users/create', 'Api\v1\UserController@create');
    $router->post('/users/edit', 'Api\v1\UserController@edit');
    $router->any('/users/show', 'Api\v1\UserController@show');
    $router->post('/users/remove', 'Api\v1\UserController@destroy');
    $router->post('/users/bulk-edit', 'Api\v1\UserController@bulkEdit');
    $router->any('/users/relations', 'Api\v1\UserController@relations');

    //Screenshots routes
    $router->any('/screenshots/list', 'Api\v1\ScreenshotController@index');
    $router->any('/screenshots/dashboard', 'Api\v1\ScreenshotController@dashboard');
    $router->post('/screenshots/create', 'Api\v1\ScreenshotController@create');
    $router->post('/screenshots/edit', 'Api\v1\ScreenshotController@edit');
    $router->any('/screenshots/show', 'Api\v1\ScreenshotController@show');
    $router->post('/screenshots/remove', 'Api\v1\ScreenshotController@destroy');

    //Time Intervals routes
    $router->any('/time-intervals/list', 'Api\v1\TimeIntervalController@index');
    $router->post('/time-intervals/create', 'Api\v1\TimeIntervalController@create');
    $router->post('/time-intervals/edit', 'Api\v1\TimeIntervalController@edit');
    $router->any('/time-intervals/show', 'Api\v1\TimeIntervalController@show');
    $router->post('/time-intervals/remove', 'Api\v1\TimeIntervalController@destroy');

    //Time routes
    $router->any('/time/total', 'Api\v1\TimeController@total');
    $router->any('/time/project', 'Api\v1\TimeController@project');
    $router->any('/time/tasks', 'Api\v1\TimeController@tasks');
    $router->any('/time/task', 'Api\v1\TimeController@task');
    $router->any('/time/task-user', 'Api\v1\TimeController@taskUser');

    //Role routes
    $router->any('/roles/list', 'Api\v1\RolesController@index');
    $router->post('/roles/create', 'Api\v1\RolesController@create');
    $router->post('/roles/edit', 'Api\v1\RolesController@edit');
    $router->any('/roles/show', 'Api\v1\RolesController@show');
    $router->post('/roles/remove', 'Api\v1\RolesController@destroy');
    $router->any('/roles/allowed-rules', 'Api\v1\RolesController@allowedRules');

    //Rule routes
    $router->post('/rules/edit', 'Api\v1\RulesController@edit');
    $router->post('/rules/bulk-edit', 'Api\v1\RulesController@bulkEdit');
    $router->any('/rules/actions', 'Api\v1\RulesController@actions');
});
