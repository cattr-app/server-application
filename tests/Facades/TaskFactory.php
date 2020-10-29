<?php

namespace Tests\Facades;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Tests\Factories\TaskFactory as BaseTaskFactory;

/**
 * @method static Task create(array $attributes = [])
 * @method static Collection createMany(int $amount = 1)
 * @method static BaseTaskFactory forUser(User $user)
 * @method static array createRandomModelData()
 * @method static BaseTaskFactory withIntervals(int $quantity = 1)
 */
class TaskFactory extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return BaseTaskFactory::class;
    }

    /**
     * Resolve a new instance for the facade
     *
     * @return mixed
     */
    public static function refresh()
    {
        static::clearResolvedInstance(static::getFacadeAccessor());

        return static::getFacadeRoot();
    }
}
