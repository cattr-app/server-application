<?php

\Illuminate\Support\Facades\Route::group([
    'prefix' => 'gitlabintegration',
    'middleware' => 'auth:api',
    'namespace' => 'Modules\GitlabIntegration\Http\Controllers'
], function (\Illuminate\Routing\Router $router) {
    $router->group([
        'prefix' => 'settings'
    ], function (\Illuminate\Routing\Router $router) {
        $router->post('get', 'SettingsController@get');
        $router->post('set', 'SettingsController@set');
    });
});