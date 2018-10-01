<?php

Route::get('/uploads/screenshots/{screenshot}', 'ScreenshotController@screenshot');
Route::get('/uploads/screenshots/thumbs/{screenshot}', 'ScreenshotController@thumbnail');
Route::any('/{any1?}/{any2?}/{any3?}/{any4?}/{any5?}', 'HomeController@index');
