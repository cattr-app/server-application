<?php

namespace Tests\Factories;

use App\Models\Project;
use App\User;
use Tests\Factories\Facades\ProjectFactory;
use Tests\Factories\Facades\IntervalFactory;
use Tests\Factories\Facades\UserFactory;
use Faker\Factory as FakerFactory;
use App\Models\Task;

/**
 * Class TaskFactory
 * @package Tests\Factories
 */
class TaskFactory extends AbstractFactory
{
    private const DESCRIPTION_LENGTH = 10;
    private const PRIORITY_ID = 2;

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
     * @param int $quantity
     * @return self
     */
    public function withIntervals(int $quantity = 1): self
    {
        $this->needsIntervals = $quantity;

        return $this;
    }

    /**
     * @param User $user
     * @return self
     */
    public function forUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param Project $project
     * @return self
     */
    public function forProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }


    /**
     * @param array $attributes
     * @return Task
     */
    public function create(array $attributes = []): Task
    {
        $taskData = $this->getRandomTaskData();

        if ($attributes) {
            $taskData = array_merge($taskData, $attributes);
        }

        $task = Task::make($taskData);

        $this->defineProject($task);
        $this->defineUser($task);

        $task->save();

        if ($this->needsIntervals) {
            $this->createIntervals($task);
        }

        return $task;
    }

    /**
     * @return array
     */
    public function getRandomTaskData(): array
    {
        $faker = FakerFactory::create();

        return [
            'task_name' => $faker->jobTitle,
            'description' => $faker->text(self::DESCRIPTION_LENGTH),
            'active' => true,
            'priority_id' => self::PRIORITY_ID,
        ];
    }

    /**
     * @param Task $task
     */
    private function defineProject(Task &$task)
    {
        if (!$this->project) {
            $this->project = ProjectFactory::create();
        }

        $task->project_id = $this->project->id;
    }

    /**
     * @param Task $task
     */
    private function defineUser(Task &$task)
    {
        if (!$this->user) {
            $this->user = UserFactory::create();
        }

        $task->user_id = $this->user->id;
    }

    /**
     * @param Task $task
     */
    private function createIntervals(Task $task): void
    {
        do {
            IntervalFactory::forUser($this->user)
                ->forTask($task)
                ->create();
        } while (--$this->needsIntervals);
    }
}
