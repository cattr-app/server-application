<?php


Route::get('/test-module', function () {
    $data = [
        'asdasd' => 'qweqwe',
    ];

    dump($data);


    // Return filtered value
    $a = Filter::fire('answer.success.item.create.test', $data);

    // Static event/action
    Event::fire('answer.success.item.create.test', $data);

    dd($a);
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
