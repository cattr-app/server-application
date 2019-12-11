<?php

namespace App\Models\Factories;

use App\Models\Project;
use App\User;
use Faker\Generator as Faker;
use App\Models\Task;

class TaskFactory
{
    private const DESCRIPTION_LENGTH = 10;
    private const PRIORITY_ID = 2;

    /**
     * @var Task
     */
    private $task;

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
        $this->task = Task::make($this->getRandomTaskData());
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
     * @return $this
     */
    public function withIntervals(int $quantity = 1): self
    {
        $this->needsIntervals = $quantity;
        return $this;
    }

    /**
     * @param $user
     * @return $this
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
     * @return $this
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
     * @return Task
     */
    public function create(): Task
    {
        if (!$this->user) {
            $this->user = app(UserFactory::class)->create();
        }

        $this->task->user()->associate($this->user);

        if (!$this->project) {
            $this->project = app(ProjectFactory::class)->create();
        }

        $this->task->project()->associate($this->project);
        $this->task->save();

        if ($this->needsIntervals) {
            $this->createIntervals();
        }

        return $this->task;
    }

    private function createIntervals(): void
    {
        $intervals = [];

        while ($this->needsIntervals--) {
            $intervals[] = app(IntervalFactory::class)
                ->linkUser($this->user)
                ->linkTask($this->task)
                ->create();
        }
    }
}
