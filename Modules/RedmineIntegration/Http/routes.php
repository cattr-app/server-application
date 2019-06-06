<?php
Route::group([
    'prefix' => 'redmineintegration',
    'middleware' => 'auth:api',
    'namespace' => 'Modules\RedmineIntegration\Http\Controllers'],
    function (\Illuminate\Routing\Router $router) {
        $router->get('/', 'RedmineIntegrationController@index');

        // Task routes
        $router->post('/tasks/synchronize', 'TaskRedmineController@synchronize');

        //Project routes
        $router->post('/projects/synchronize', 'ProjectRedmineController@synchronize');

        //Time Entry routes
        $router->post('/timeentries/create', 'TimeEntryRedmineController@create');

        //Redmine Settings routes
        $router->post('/settings/update', 'RedmineSettingsController@updateSettings');
        $router->post('/settings/get', 'RedmineSettingsController@getSettings');
    });


