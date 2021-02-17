<?php

namespace Tests\Factories;

use App\Models\Priority;
use App\Models\Project;
use App\Models\Status;
use App\Models\Task;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Model;
use Tests\Facades\IntervalFactory;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;

class TaskFactory extends Factory
{
    private const DESCRIPTION_LENGTH = 10;

    private int $intervalsAmount = 0;

    private ?User $user = null;
    private ?Project $project = null;
    private Task $task;

    protected function getModelInstance(): Model
    {
        return $this->task;
    }

    public function withIntervals(int $quantity = 1): self
    {
        $this->intervalsAmount = $quantity;

        return $this;
    }

    public function forUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function forProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function create(array $attributes = []): Task
    {
        $modelData = $this->createRandomModelData();

        $this->task = Task::make($modelData);

        $this->defineProject();
        $this->defineUser();

        $this->task->save();

        if (isset($this->user)) {
            $this->task->users()->attach($this->user->id);
        }

        if ($this->intervalsAmount) {
            $this->createIntervals();
        }

        if ($this->timestampsHidden) {
            $this->hideTimestamps();
        }

        return $this->task;
    }

    public function createRandomModelData(): array
    {
        $faker = FakerFactory::create();

        return [
            'task_name' => $faker->jobTitle,
            'description' => $faker->text(self::DESCRIPTION_LENGTH),
            'priority_id' => Priority::min('id'),
            'status_id' => Status::min('id'),
        ];
    }

    private function defineProject(): void
    {
        if (!isset($this->project)) {
            $this->project = ProjectFactory::create();
        }

        $this->task->project_id = $this->project->id;
    }

    private function defineUser(): void
    {
        if (!isset($this->user)) {
            $this->user = UserFactory::create();
        }
    }

    private function createIntervals(): void
    {
        do {
            IntervalFactory::forUser($this->user)
                ->forTask($this->task)
                ->create();
        } while (--$this->intervalsAmount);
    }
}
