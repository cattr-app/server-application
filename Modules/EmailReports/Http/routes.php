<?php

use Illuminate\Routing\Router;

Route::group(
    [
    'prefix' => 'v1',
    'middleware' => 'auth:api',
    'namespace' => 'Modules\EmailReports\Http\Controllers'],
    static function (Router $router) {
        $router->post('/email-reports/create', 'EmailReportsController@create');
        $router->any('/email-reports/list', 'EmailReportsController@index');
        $router->post('/email-reports/edit', 'EmailReportsController@edit');
        $router->any('/email-reports/show', 'EmailReportsController@show');
        $router->post('/email-reports/remove', 'EmailReportsController@destroy');
        $router->any('/email-reports/count', 'EmailReportsController@count');
    }
);
