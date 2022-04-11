<?php

namespace Database\Factories;

use App\Models\Priority;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'task_name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'assigned_by' => fn() => User::where(['is_admin' => 1])->first()->id,
            'important' => $this->faker->boolean,
            'priority_id' => Priority::inRandomOrder()->first()->id,
            'status_id' => Status::inRandomOrder()->first()->id,
        ];
    }
}
