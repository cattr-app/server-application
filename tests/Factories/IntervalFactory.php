<?php

namespace Tests\Factories;

use Faker\Factory as FakerFactory;
use App\User;
use App\Models\TimeInterval;
use App\Models\Task;
use Illuminate\Support\Collection;

/**
 * Class IntervalFactory
 * @package Tests\Factories
 */
class IntervalFactory
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
     * @param $task
     * @return self
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

    /**
     * @param array $attributes
     * @return TimeInterval
     */
    protected function make(array $attributes = []): TimeInterval
    {
        $intervalData = $this->getRandomIntervalData();

        if ($attributes) {
            $intervalData = array_merge($intervalData, $attributes);
        }

        return TimeInterval::make($intervalData);
    }

    /**
     * @param array $attributes
     * @return TimeInterval
     */
    public function create(array $attributes = []): TimeInterval
    {
        $interval = $this->make($attributes);

        $interval->user()->associate($this->user);

        if (!$this->task) {
            $this->task = app(TaskFactory::class)
                ->linkUser($this->user)
                ->create();
        }

        $interval->task()->associate($this->task);

        $interval->save();

        return $interval;
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
}
