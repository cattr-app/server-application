<?php

use Illuminate\Routing\Router;

Route::middleware('auth:api')->group(function (Router $router) {
    $router->get('/', 'RedmineIntegrationController@index')->name('index');

    // Task routes
    $router->post('/tasks/synchronize', 'TaskRedmineController@synchronize')->name('task.synchronize');

    // Project routes
    $router->post('/projects/synchronize', 'ProjectRedmineController@synchronize')
        ->name('projects.synchronize');

    // Time Entry routes
    $router->put('/time-entries', 'TimeEntryRedmineController@create')->name('time-entries.put');

    // Redmine Settings routes
    $router->patch('/settings', 'RedmineSettingsController@updateSettings')
        ->name('settings.update');
    $router->get('/settings', 'RedmineSettingsController@getSettings')->name('settings.get');

    $router->group(['prefix' => '/settings/data', 'as' => 'settings.data.'], function () use ($router) {
        $router->get('internal-priorities',
            'RedmineSettingsController@getInternalPriorities')->name('internal-priorities');
    });
});
