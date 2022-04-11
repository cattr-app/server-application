<?php

namespace Database\Factories;

use App\Models\Priority;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'company_id' => $this->faker->numberBetween(1, 10),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'important' => $this->faker->boolean,
            'source' => 'internal',
            'default_priority_id' => function () {
                return Priority::orderByRaw('RAND()')->first()->id;
            },
            'created_at' => now()
        ];
    }
}
