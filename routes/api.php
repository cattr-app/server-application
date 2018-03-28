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
    $router->post('/webservice/create', 'Api\v1\WebserviceController@create');

    //Projects routes
    $router->any('/projects/list', 'Api\v1\ProjectController@index');
    $router->post('/projects/create', 'Api\v1\ProjectController@create');
    $router->post('/projects/edit', 'Api\v1\ProjectController@edit');
    $router->any('/projects/show', 'Api\v1\ProjectController@show');
    $router->post('/projects/remove', 'Api\v1\ProjectController@destroy');

    //Tasks routes
    $router->any('/tasks/list', 'Api\v1\TaskController@index');
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

    //Screenshots routes
    $router->any('/screenshots/list', 'Api\v1\ScreenshotController@index');
    $router->post('/screenshots/create', 'Api\v1\ScreenshotController@create');
    $router->post('/screenshots/edit', 'Api\v1\ScreenshotController@edit');
    $router->any('/screenshots/show', 'Api\v1\ScreenshotController@show');
    $router->post('/screenshots/remove', 'Api\v1\ScreenshotController@destroy');

    //Time Intervals routes
    $router->any('/timeintervals/list', 'Api\v1\TimeIntervalController@index');
    $router->post('/timeintervals/create', 'Api\v1\TimeIntervalController@create');
    $router->post('/timeintervals/edit', 'Api\v1\TimeIntervalController@edit');
    $router->any('/timeintervals/show', 'Api\v1\TimeIntervalController@show');
    $router->post('/timeintervals/remove', 'Api\v1\TimeIntervalController@destroy');

    //role routes
    $router->any('/roles/list', 'Api\v1\RolesController@index');
    $router->post('/roles/create', 'Api\v1\RolesController@create');
    $router->post('/roles/edit', 'Api\v1\RolesController@edit');
    $router->any('/roles/show', 'Api\v1\RolesController@show');
    $router->post('/roles/remove', 'Api\v1\RolesController@destroy');

    $router->any('/actions/list', 'Api\v1\ActionsController@list');
    $router->any('/allowed/list', 'Api\v1\AllowedController@list');

    $router->post('/rules/edit', 'Api\v1\RulesController@edit');

});
