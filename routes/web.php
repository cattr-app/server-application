<?php

Route::get('/{any?}', function () {
    return view('welcome');
});

Route::get('/auth/{any?}', function () {
    return view('welcome');
});
