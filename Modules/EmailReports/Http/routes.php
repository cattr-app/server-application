<?php
Route::group([
    'prefix' => 'v1',
    'middleware' => 'auth:api',
    'namespace' => 'Modules\EmailReports\Http\Controllers'],
    function (\Illuminate\Routing\Router $router) {
        $router->post('/email-reports/create', 'EmailReportsController@create');
        $router->post('/email-reports/list', 'EmailReportsController@index');
        $router->post('/email-reports/edit', 'EmailReportsController@edit');
        $router->any('/email-reports/show', 'EmailReportsController@show');
        $router->post('/email-reports/remove', 'EmailReportsController@destroy');
        $router->post('/email-reports/count', 'EmailReportsController@count');
    });


