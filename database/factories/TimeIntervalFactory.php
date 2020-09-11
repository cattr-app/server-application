<?php

use App\Models\TimeInterval;
use Illuminate\Database\Eloquent\Factory;

/** @var Factory $factory */
$factory->define(TimeInterval::class, static function (Faker $faker) {
    $mouseFill = mt_rand(0, 100);
    $keyboardFill = mt_rand(0, 100 - $mouseFill);
    $activityFill = $mouseFill + $keyboardFill;

    return [
        'task_id' => 1,
        'user_id' => 1,
        'start_at' => date('Y-m-d H:i:s', now()),
        'end_at' => date('Y-m-d H:i:s', now()->addMinutes(5)),
        'activity_fill' => $activityFill,
        'mouse_fill' => $mouseFill,
        'keyboard_fill' => $keyboardFill,
    ];
});
