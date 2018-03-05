<?php


Route::get('/test-module', function () {
    $data = [
        'asdasd' => 'qweqwe',
    ];

    dump($data);

    $a = Event::fire('answer.success.item.create.test', $data);

    dd($a[0]);
});


Route::get('/{any?}', function () {
    return view('welcome');
});

Route::get('/{any1?}/{any2?}', function () {
    return view('welcome');
});

Route::get('/{any1?}/{any2?}/{any3?}/{any4?}', function () {
    return view('welcome');
});

Route::get('/{any1?}/{any2?}/{any3?}/{any4?}/{any5?}', function () {
    return view('welcome');
});
