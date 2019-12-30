<?php

namespace Tests\Factories;

use Faker\Factory as FakerFactory;
use App\User;
use App\Models\TimeInterval;
use App\Models\Task;

/**
 * Class IntervalFactory
 * @package Tests\Factories
 */
class IntervalFactory extends AbstractFactory
{
    private const COUNT_MOUSE = 42;
    private const COUNT_KEYBOARD = 43;
    private const INTERVAL_DURATION_SECONDS = 299;

    /**
     * @var User
     */
    private $user;

    /**
     * @var Task
     */
    private $task;

    /**
     * @return array
     */
    private function getRandomIntervalData(): array
    {
        $faker = FakerFactory::create();
        $time = $faker->unique()->unixTime();

        return [
            'start_at' => date('Y-m-d H:i:s', $time - self::INTERVAL_DURATION_SECONDS),
            'end_at' => date('Y-m-d H:i:s', $time),
            'count_mouse' => self::COUNT_MOUSE,
            'count_keyboard' => self::COUNT_KEYBOARD
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
    private function defineUser(TimeInterval &$interval)
    {
        if (!$this->user) {
            $this->user = app(UserFactory::class)->create();
        }

        $interval->user_id = $this->user->id;
    }

    /**
     * @param TimeInterval $interval
     */
    private function defineTask(TimeInterval &$interval)
    {
        if (!$this->task) {
            $this->task = app(TaskFactory::class)
                ->forUser($this->user)
                ->create();
        }

        $interval->task_id = $this->task->id;
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

        return $interval;
    }
}
