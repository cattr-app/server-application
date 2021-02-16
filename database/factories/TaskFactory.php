<?php

namespace Database\Factories;

use App\Models\Project;
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
        $projectId = Project::first()->id;
        $userId = User::where(['email' => 'admin@example.com'])->first()->id;

        return [
            'project_id' => $projectId,
            'task_name' => $this->faker->unique()->text,
            'description' => $this->faker->unique()->text,
            'status_id' => Status::inRandomOrder()->first()->id,
            'user_id' => $userId,
            'assigned_by' => $userId,
            'priority_id' => 2, // Normal
        ];
    }
}
