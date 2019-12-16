<?php

namespace Tests\Factories;

use Faker\Factory as FakerFactory;
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
     * @var int
     */
    private $needsTasks = 0;

    /**
     * @var int
     */
    private $needsIntervals = 0;

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
     * @return array
     */
    public function getRandomProjectData(): array
    {
        $faker = FakerFactory::create();

        return [
            'company_id' => self::COMPANY_ID,
            'name' => $faker->company,
            'description' => $faker->text(self::DESCRIPTION_LENGTH),
        ];
    }

    /**
     * @param array $attributes
     * @return Project
     */
    protected function make(array $attributes = []): Project
    {
        $projectData = $this->getRandomProjectData();

        if ($attributes) {
            $projectData = array_merge($projectData, $attributes);
        }

        return Project::make($projectData);
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

}
