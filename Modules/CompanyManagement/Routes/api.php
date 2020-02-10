<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->prefix('companymanagement')->group(function () {
    Route::get('getData', 'CompanyManagementController@getData')->name('get-data');
    Route::post('save', 'CompanyManagementController@save')->name('save');
});
