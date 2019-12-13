<?php

namespace Tests\Factories;

use Faker\Generator as Faker;
use App\Models\Project;

/**
 * Class ProjectFactory
 * @package Tests\Factories
 */
class ProjectFactory
{
    private const COMPANY_ID = 1;
    private const DESCRIPTION_LENGTH = 300;

    /**
     * @var Project
     */
    private $project;

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
        $this->project = Project::make($this->getRandomProjectData());
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
     * @return Project
     */
    public function create(): Project
    {
        $this->project->save();

        if ($this->needsTasks) {
            $this->createTasks();
        }

        return $this->project;
    }

    private function createTasks(): void
    {
        $tasks = [];

        while ($this->needsTasks--) {
            $tasks[] = app(TaskFactory::class)
                ->withIntervals($this->needsIntervals)
                ->linkProject($this->project)
                ->create();
        }
    }
}
