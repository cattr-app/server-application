<?php

namespace Tests\Factories;

use App\Models\Project;
use Faker\Factory as FakerFactory;
use Tests\Facades\TaskFactory;

class ProjectFactory extends Factory
{
    private const COMPANY_ID = 1;
    private const DESCRIPTION_LENGTH = 300;

    private int $needsTasks = 0;
    private int $needsIntervals = 0;
    private array $users = [];
    private ?Project $project = null;


    protected function getModelInstance(): Project
    {
        return $this->project;
    }

    public function withTasks(int $quantity = 1): self
    {
        $this->needsTasks = $quantity;
        return $this;
    }

    public function create(): Project
    {
        $modelData = $this->createRandomModelData();

        $this->project = Project::create($modelData);

        if ($this->users) {
            foreach ($this->users as $user) {
                $this->project->users()->attach($user->id);
            }
        }

        if ($this->needsTasks) {
            $this->createTasks();
        }

        if ($this->timestampsHidden) {
            $this->hideTimestamps();
        }

        $this->hideCanAttribute();

        return $this->project;
    }

    public function forUsers(array $users): self
    {
        $this->users = $users;
        return $this;
    }

    public function createRandomModelData(): array
    {
        $faker = FakerFactory::create();

        return [
            'company_id' => self::COMPANY_ID,
            'name' => $faker->company,
            'description' => $faker->text(self::DESCRIPTION_LENGTH),
            'source' => 'internal',
        ];
    }

    protected function createTasks(): void
    {
        do {
            TaskFactory::withIntervals($this->needsIntervals)
                ->forProject($this->project)
                ->create();
        } while (--$this->needsTasks);
    }
}
