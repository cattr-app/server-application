<?php

Route::group(['middleware' => 'web', 'prefix' => 'eventlistener', 'namespace' => 'Modules\EventListener\Http\Controllers'], function()
{
    Route::get('/event-listener-module', 'EventListenerController@index');
});
