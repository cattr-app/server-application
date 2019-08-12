<?php
Route::group([
    'prefix' => 'v1',
    'middleware' => 'auth:api',
    'namespace' => 'Modules\Invoices\Http\Controllers'],
    function (\Illuminate\Routing\Router $router) {
        $router->post('/invoices', 'InvoicesController@index');
        $router->post('/invoices/update', 'InvoicesController@update');
        $router->post('/invoices/projects', 'InvoicesController@projects');
        $router->post('/invoices/default', 'InvoicesController@getDefaultRate');
        $router->post('/invoices/default/set', 'InvoicesController@setDefaultRate');
    });


