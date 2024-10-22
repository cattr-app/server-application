<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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
        $project = Project::query()->inRandomOrder()->first();
        return [
            'project_id' =>  $project->id,
            'task_name' => fake()->sentence(3),
            'description' => fake()->paragraph,
            'assigned_by' => fn() => User::where(['role_id' => Role::ADMIN])->first()->id,
            'important' => fake()->boolean,
            'priority_id' => Priority::inRandomOrder()->first()->id,
            'status_id' => Status::inRandomOrder()->first()->id,
            'start_date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays($this->faker->numberBetween(1,30)),
        ];
    }
}
