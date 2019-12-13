<?php

namespace Tests\Factories;

use Faker\Generator as Faker;
use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * Class ProjectFactory
 * @package Tests\Factories
 */
class ProjectFactory
{
    private const COMPANY_ID = 1;
    private const DESCRIPTION_LENGTH = 300;

    /**
     * @var Faker
     */
    private $faker;

    /**
     * @var int
     */
    private $needsTasks = 0;

    /**
     * @var int
     */
    private $needsIntervals = 0;

    /**
     * ProjectFactory constructor.
     * @param Faker $faker
     */
    public function __construct(Faker $faker)
    {
        $this->faker = $faker;
    }

    /**
     * @return array
     */
    public function getRandomProjectData(): array
    {
        return [
            'company_id' => self::COMPANY_ID,
            'name' => $this->faker->company,
            'description' => $this->faker->text(self::DESCRIPTION_LENGTH),
        ];
    }

    /**
     * @param int $quantity
     * @return self
     */
    public function withTasks(int $quantity = 1): self
    {
        $this->needsTasks = $quantity;
        return $this;
    }

    /**
     * @param int $quantity
     * @return self
     */
    public function withIntervals(int $quantity = 1): self
    {
        $this->needsIntervals = $quantity;
        return $this;
    }

    /**
     * @param int $amount
     * @return Collection
     */
    public function createMany($amount = 1): Collection
    {
        $collection = collect();

        while ($amount--) {
            $collection->push($this->create());
        }

        return $collection;
    }

    /**
     * @param array $attributes
     * @return Project
     */
    public function create(array $attributes = []): Project
    {
        $project = $this->make($attributes);

        $project->save();

        if ($this->needsTasks) {
            $this->createTasks($project);
        }

        return $project;
    }

    /**
     * @param array $attributes
     * @return Project
     */
    public function make(array $attributes = []): Project
    {
        $projectData = $this->getRandomProjectData();

        if ($attributes) {
            $projectData = array_merge($projectData, $attributes);
        }

        return Project::make($projectData);
    }

    /**
     * @param Project $project
     */
    private function createTasks(Project $project): void
    {
        $tasks = [];

        while ($this->needsTasks--) {
            $tasks[] = app(TaskFactory::class)
                ->withIntervals($this->needsIntervals)
                ->linkProject($project)
                ->create();
        }
    }
}
