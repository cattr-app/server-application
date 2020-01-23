<?php

/** @var Factory $factory */

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;

$factory->define(Task::class, function (Faker $faker) {
    $projectId = Project::first()->id;
    $userId = User::where(['email' => 'admin@example.com'])->first()->id;

    return [
        'project_id' => $projectId,
        'task_name' => $faker->unique()->text,
        'description' => $faker->unique()->text,
        'active' => true,
        'user_id' => $userId,
        'assigned_by' => $userId,
        'priority_id' => 2, // Normal
    ];
});
