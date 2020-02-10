<?php

namespace Tests\Factories;

use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;

/**
 * Class IntervalFactory
 */
class IntervalFactory extends AbstractFactory
{
    /**
     * @var User
     */
    private $user;

    /**
     * @var Task
     */
    private $task;

    /**
     * @var bool
     */
    private $randomRelations = false;

    /**
     * @return array
     */
    public function getRandomIntervalData(): array
    {
        $randomDateTime = FakerFactory::create()->unique()->dateTimeThisYear();
        $randomDateTime = Carbon::instance($randomDateTime);

        return [
            'end_at' => $randomDateTime->toIso8601String(),
            'start_at' => $randomDateTime->subSeconds(random_int(1, 3600))->toIso8601String(),
            'count_mouse' => random_int(1, 1000),
            'count_keyboard' => random_int(1, 1000)
        ];
    }

    /**
     * @return array
     */
    public function generateRandomIntervalData(): array
    {
        $randomDateTime = FakerFactory::create()->unique()->dateTimeThisYear();
        $randomDateTime = Carbon::instance($randomDateTime);

        return [
            'task_id' => TaskFactory::create()->id,
            'user_id' => UserFactory::create()->id,
            'end_at' => $randomDateTime->toIso8601String(),
            'start_at' => $randomDateTime->subSeconds(random_int(1, 3600))->toIso8601String(),
            'count_mouse' => random_int(1, 1000),
            'count_keyboard' => random_int(1, 1000)
        ];
    }

    /**
     * @return array
     */
    public function generateRandomManualIntervalData(): array
    {
        $randomDateTime = FakerFactory::create()->unique()->dateTimeThisYear();
        $randomDateTime = Carbon::instance($randomDateTime);

        return [
            'task_id' => TaskFactory::create()->id,
            'user_id' => UserFactory::create()->id,
            'end_at' => $randomDateTime->toIso8601String(),
            'start_at' => $randomDateTime->subSeconds(random_int(1, 3600))->toIso8601String(),
        ];
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
     * @param Task $task
     *
     * @return self
     */
    public function forTask(Task $task): self
    {
        $this->task = $task;

        return $this;
    }

    /**
     * @param TimeInterval $interval
     */
    private function defineUser(TimeInterval $interval): void
    {
        if ($this->randomRelations || !$this->user) {
            $this->user = UserFactory::create();
        }

        $interval->user_id = $this->user->id;
    }

    /**
     * @param TimeInterval $interval
     */
    private function defineTask(TimeInterval $interval): void
    {
        if ($this->randomRelations || !$this->task) {
            $this->task = TaskFactory::forUser($this->user)->create();
        }

        $interval->task_id = $this->task->id;
    }

    /**
     * @return self
     */
    public function withRandomRelations(): self
    {
        $this->randomRelations = true;

        return $this;
    }


    /**
     * @param array $attributes
     * @return TimeInterval
     */
    public function create(array $attributes = []): TimeInterval
    {
        $intervalData = $this->getRandomIntervalData();

        if ($attributes) {
            $intervalData = array_merge($intervalData, $attributes);
        }

        $interval = TimeInterval::make($intervalData);

        $this->defineUser($interval);
        $this->defineTask($interval);

        $interval->save();

        if ($this->timestampsHidden) {
            $this->hideTimestamps($interval);
        }

        return $interval;
    }
}
