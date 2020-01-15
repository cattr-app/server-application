<?php

namespace Tests\Facades;

use App\Models\Task;
use App\User;
use Tests\Factories\IntervalFactory as BaseIntervalFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use App\Models\TimeInterval;

/**
 * @method static TimeInterval create(array $attributes = [])
 * @method static Collection createMany(int $amount = 1)
 * @method static array getRandomIntervalData()
 * @method static BaseIntervalFactory forUser(User $user)
 * @method static BaseIntervalFactory forTask(Task $task)
 *
 * @mixin BaseIntervalFactory
 */
class IntervalFactory extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BaseIntervalFactory::class;
    }
}
