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
            'company_id' => fake()->numberBetween(1, 10),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph,
            'important' => fake()->boolean,
            'source' => 'internal',
            'default_priority_id' => fn () => Priority::orderByRaw('RAND()')->first()->id,
            'created_at' => now()
        ];
    }
}
