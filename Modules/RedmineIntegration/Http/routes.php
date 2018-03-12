<?php

Route::group(['prefix' => 'redmineintegration', 'namespace' => 'Modules\RedmineIntegration\Http\Controllers'], function()
{
    Route::get('/', 'RedmineIntegrationController@index');

    // Issue routes
    Route::get('/issues', 'IssueRedmineController@list');
    Route::get('/issues/show/{id}', 'IssueRedmineController@show');

    //Project routes
    Route::get('/projects', 'ProjectRedmineController@list');
    Route::get('/projects/show/{id}', 'ProjectRedmineController@show');

    //User routes
    Route::get('/users', 'UserRedmineController@list');
    Route::get('/users/show/{id}', 'UserRedmineController@show');

    //Time Entry routes
    Route::get('/timeentries', 'TimeEntryRedmineController@list');
    Route::get('/timeentries/show/{id}', 'TimeEntryRedmineController@show');

});
