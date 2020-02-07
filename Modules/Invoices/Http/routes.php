<?php
Route::group([
    'prefix' => 'v1',
    'middleware' => 'auth:api',
    'namespace' => 'Modules\Invoices\Http\Controllers'],
    function (\Illuminate\Routing\Router $router) {
        $router->post('/invoices/list', 'InvoicesController@index');
        $router->post('/invoices/setProjectRate', 'InvoicesController@setProjectRate');
        $router->post('/invoices/setDefaultRate', 'InvoicesController@setDefaultRate');
    });


