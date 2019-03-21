<?php
use Illuminate\Routing\Router;

Route::group([
    'prefix' => 'redmineintegration',
    'middleware' => 'auth:api' ,
    'namespace' => 'Modules\RedmineIntegration\Http\Controllers'],
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




Route::group([
    'middleware' => 'api',
    'prefix' => 'api',
], function (Router $router) {
    $router->group([
        'middleware' => 'auth:api',
        'prefix' => 'v1',
        'namespace' => 'Modules\RedmineIntegration\Http\Controllers',
    ], function (Router $router) {
        $router->post('redmine/statuses', 'RedmineIntegrationController@getStatuses');
    });

});




