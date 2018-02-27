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
    $router->post('login', 'AuthController@login');
    $router->post('logout', 'AuthController@logout');
    $router->post('refresh', 'AuthController@refresh');
    $router->post('me', 'AuthController@me');
});

Route::group([
    'middleware' => 'auth:api',
    'prefix' => 'v1',
], function (Router $router) {
    $router->post('/webservice/create', 'Api\v1\WebserviceController@create');
    $router->post('/projects', 'Api\v1\ProjectController@index');
    $router->post('/projects/create', 'Api\v1\ProjectController@create');
    //$router->resource('/webservice', 'Api\v1\WebserviceController');
});

Route::middleware('auth:api')->get('/user', function(Request $request) {
    return $request->user();
})
;
