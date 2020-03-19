<?php

use Illuminate\Routing\Router;

Route::group(
    [
        'prefix' => 'v1',
        'middleware' => 'auth:api',
        'namespace' => 'Modules\Invoices\Http\Controllers'],
    static function (Router $router) {
        $router->post('/invoices/list', 'InvoicesController@index');
        $router->post('/invoices/setProjectRate', 'InvoicesController@setProjectRate');
        $router->post('/invoices/setDefaultRate', 'InvoicesController@setDefaultRate');
    }
);
