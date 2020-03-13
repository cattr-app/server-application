<?php

use Illuminate\Routing\Router;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->group(function (Router $router) {
    $router->get('/settings', 'SettingsController@get')->name('settings.get');
    $router->post('/settings', 'SettingsController@set')->name('settings.set');
    $router->get('/companysettings', 'CompanySettingsController@get')->name('companysettings.get');
    $router->post('/companysettings', 'CompanySettingsController@set')->name('companysettings.set');
});
