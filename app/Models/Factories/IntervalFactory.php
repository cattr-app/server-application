<?php

namespace App\Models\Factories;

use Faker\Generator as Faker;
use App\User;
use App\Models\TimeInterval;
use App\Models\Task;

/**
 * Class IntervalFactory
 * @package App\Models\Factories
 */
class IntervalFactory
{
    private const COUNT_MOUSE = 42;
    private const COUNT_KEYBOARD = 43;
    private const INTERVAL_DURATION_SECONDS = 299;
    /**
     * @var TimeInterval
     */
    private $interval;
    /**
     * @var Faker
     */
    private $faker;
    /**
     * @var User
     */
    private $user;
    /**
     * @var Task
     */
    private $task;

    /**
     * ProjectFactory constructor.
     * @param Faker $faker
     */
    public function __construct(Faker $faker)
    {
        $this->faker = $faker;
        $this->interval = TimeInterval::make($this->getRandomIntervalData());
    }

    /**
     * @return array
     */
    public function getRandomIntervalData(): array
    {
        $time = $this->faker->unique()->unixTime();

        return [
            'start_at' => date('Y-m-d H:i:s', $time - self::INTERVAL_DURATION_SECONDS),
            'end_at' => date('Y-m-d H:i:s', $time),
            'count_mouse' => self::COUNT_MOUSE,
            'count_keyboard' => self::COUNT_KEYBOARD
        ];
    }

    /**
     * @return TimeInterval
     */
    public function create(): TimeInterval
    {
        if (!$this->user) {
            $this->user = app(UserFactory::class)->create();
        }

        $this->interval->user()->associate($this->user);

        if (!$this->task) {
            $this->task = app(TaskFactory::class)
                ->linkUser($this->user)
                ->create();
        }

        $this->interval->task()->associate($this->task);
        $this->interval->save();

        return $this->interval;
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
     * @param $task
     * @return $this
     */
    public function linkTask($task): self
    {
        if ($task instanceof Task) {
            $this->task = $task;
            return $this;
        }

        $this->task = Task::find($task);
        return $this;
    }
}
