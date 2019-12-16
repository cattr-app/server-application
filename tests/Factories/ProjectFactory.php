<?php

namespace Tests\Factories;

use Faker\Factory as FakerFactory;
use App\Models\Project;

/**
 * Class ProjectFactory
 * @package Tests\Factories
 */
class ProjectFactory extends AbstractFactory
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
     * @param int $quantity
     * @return self
     */
    public function withTasks(int $quantity = 1): self
    {
        $this->needsTasks = $quantity;

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
     * @param Project $project
     */
    protected function createTasks(Project $project): void
    {
        while ($this->needsTasks--) {
            app(TaskFactory::class)
                ->withIntervals($this->needsIntervals)
                ->forProject($project)
                ->create();
        }
    }

    /**
     * @param array $attributes
     * @return Project
     */
    public function create(array $attributes = []): Project
    {
        $projectData = $this->getRandomProjectData();

        if ($attributes) {
            $projectData = array_merge($projectData, $attributes);
        }

        $project = Project::create($projectData);

        if ($this->needsTasks) {
            $this->createTasks($project);
        }

        return $project;
    }
}
