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

Route::middleware('auth:api')->prefix('companymanagement')->group(static function () {
    Route::get('getData', 'CompanyManagement@getData')->name('get-data');
    Route::post('save', 'CompanyManagement@save')->name('save');
    Route::post('timezone/edit', 'CompanyManagement@editTimezone');
    Route::post('language/edit', 'CompanyManagement@editLanguage');
});
