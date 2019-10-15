<?php

Route::middleware('auth:api')->group(function () {
    // Task routes
    Route::post('/tasks/synchronize', 'TaskRedmineController@synchronize')->name('task.synchronize');

    // Project routes
    Route::post('/projects/synchronize', 'ProjectRedmineController@synchronize')
        ->name('projects.synchronize');

    // Time Entry routes
    Route::put('/time-entries', 'TimeEntryRedmineController@create')->name('time-entries.put');

    // Redmine Settings routes
    Route::patch('/settings', 'RedmineSettingsController@updateSettings')
        ->name('settings.update');
    Route::get('/settings', 'RedmineSettingsController@getSettings')->name('settings.get');

    Route::group(['prefix' => '/settings/data', 'as' => 'settings.data.'], function () {
        Route::get('internal-priorities',
            'RedmineSettingsController@getInternalPriorities')->name('internal-priorities');
    });
});

Route::group([
    'middleware' => 'redmineintegration.signature',
    'as' => 'plugin.',
    'prefix' => 'plugin'
], function () {
    Route::post('update', 'TaskUpdateController@handleUpdate')->name('update');
});
