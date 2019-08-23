<?php
Route::middleware('auth:api')->group(function (\Illuminate\Routing\Router $router) {
    $router->get('/settings', 'SettingsController@get')->name('settings.get');
    $router->put('/settings', 'SettingsController@set')->name('settings.set');
});
