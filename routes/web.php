<?php
use App\Http\Controllers\Controller;

Route::any('{any}', [Controller::class, 'frontendRoute'])->where('any', '^(?!api).*')->name('frontend');
