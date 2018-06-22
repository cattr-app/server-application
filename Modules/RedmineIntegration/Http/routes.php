<?php

Route::group(['prefix' => 'redmineintegration', 'namespace' => 'Modules\RedmineIntegration\Http\Controllers'],
    function () {
        Route::get('/', 'RedmineIntegrationController@index');

        // Task routes
        Route::post('/tasks/synchronize', 'TaskRedmineController@synchronize');

        //Project routes
        Route::post('/projects/synchronize', 'ProjectRedmineController@synchronize');

        //Time Entry routes
        Route::post('/timeentries/create', 'TimeEntryRedmineController@create');

        //Redmine Settings routes
        Route::post('/settings/update', 'RedmineSettingsController@updateSettings');
        Route::post('/settings/get', 'RedmineSettingsController@getSettings');
    });
