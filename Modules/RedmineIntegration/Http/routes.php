<?php

Route::group(['prefix' => 'redmineintegration', 'namespace' => 'Modules\RedmineIntegration\Http\Controllers'], function()
{
    Route::get('/', 'RedmineIntegrationController@index');

    // Task routes
    Route::get('/tasks', 'TaskRedmineController@list');
    Route::get('/tasks/show/{id}', 'TaskRedmineController@show');
    Route::get('/tasks/project/{id}', 'TaskRedmineController@getProjectTasks');
    Route::get('/tasks/user/{id}', 'TaskRedmineController@getUserTasks');
    Route::get('/tasks/synchronize', 'TaskRedmineController@synchronize');

    //Project routes
    Route::get('/projects', 'ProjectRedmineController@list');
    Route::get('/projects/show/{id}', 'ProjectRedmineController@show');
    Route::get('/projects/synchronize', 'ProjectRedmineController@synchronize');

    //User routes
    Route::get('/users', 'UserRedmineController@list');
    Route::get('/users/show/{id}', 'UserRedmineController@show');
    Route::get('/users/synchronize', 'UserRedmineController@synchronize');

    //Time Entry routes
    Route::get('/timeentries', 'TimeEntryRedmineController@list');
    Route::get('/timeentries/show/{id}', 'TimeEntryRedmineController@show');
    Route::get('/timeentries/create/{id}', 'TimeEntryRedmineController@create');
});
