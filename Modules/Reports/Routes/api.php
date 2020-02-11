<?php

use Illuminate\Http\Request;

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

Route::post('/reports/projects', 'ProjectReportsController@getReport');

/**
 * Can be used for both Timeline and Team reports because the difference just in user_ids we receive
 */
Route::get('/reports/dashboard', 'DashboardReportsController@getReport');
