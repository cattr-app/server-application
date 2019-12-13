<?php

namespace Tests\Factories;

use App\Models\Project;
use App\User;
use Faker\Generator as Faker;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Class TaskFactory
 * @package Tests\Factories
 */
class TaskFactory
{
    private const DESCRIPTION_LENGTH = 10;
    private const PRIORITY_ID = 2;

    /**
     * @var Faker
     */
    private $faker;

    /**
     * @var int
     */
    private $needsIntervals = 0;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Project
     */
    private $project;

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
    public function getRandomTaskData(): array
    {
        return [
            'task_name' => $this->faker->jobTitle,
            'description' => $this->faker->text(self::DESCRIPTION_LENGTH),
            'active' => true,
            'priority_id' => self::PRIORITY_ID,
        ];
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
     * @param $user
     * @return self
     */
    public function linkUser($user): self
    {
        if ($user instanceof User) {
            $this->user = $user;
            return $this;
        }

        $this->user = User::find($user);
        return $this;
    }

    /**
     * @param $project
     * @return self
     */
    public function linkProject($project): self
    {
        if ($project instanceof Project) {
            $this->project = $project;
            return $this;
        }

        $this->project = Project::find($project);
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
     * @return Task
     */
    public function make(array $attributes = []): Task
    {
        $taskData = $this->getRandomTaskData();

        if ($attributes) {
            $taskData = array_merge($taskData, $attributes);
        }

        return Task::make($taskData);
    }

    /**
     * @param array $attributes
     * @return Task
     */
    public function create(array $attributes = []): Task
    {
        $task = $this->make($attributes);

        if (!$this->user) {
            $this->user = app(UserFactory::class)->create();
        }

        $task->user()->associate($this->user);

        if (!$this->project) {
            $this->project = app(ProjectFactory::class)->create();
        }

        $task->project()->associate($this->project);

        $task->save();

        if ($this->needsIntervals) {
            $this->createIntervals($task);
        }

        return $task;
    }

    /**
     * @param Task $task
     */
    private function createIntervals(Task $task): void
    {
        $intervals = [];

        while ($this->needsIntervals--) {
            $intervals[] = app(IntervalFactory::class)
                ->linkUser($this->user)
                ->linkTask($task)
                ->create();
        }
    }
}
