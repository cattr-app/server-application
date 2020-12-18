<?php

namespace Tests\Facades;

use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Tests\Factories\IntervalFactory as BaseIntervalFactory;

/**
 * @method static TimeInterval create(array $attributes = [])
 * @method static Collection createMany(int $amount = 1)
 * @method static array createRandomModelData()
 * @method static array createRandomModelDataWithRelation()
 * @method static array createRandomManualModelDataWithRelations()
 * @method static BaseIntervalFactory forUser(User $user)
 * @method static BaseIntervalFactory forTask(Task $task)
 * @method static BaseIntervalFactory withRandomRelations()
 */
class IntervalFactory extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return BaseIntervalFactory::class;
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
