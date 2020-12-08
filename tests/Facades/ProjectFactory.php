<?php

namespace Tests\Facades;

use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Tests\Factories\ProjectFactory as BaseProjectFactory;

/**
 * @method static Project create(array $attributes = [])
 * @method static Collection createMany(int $amount = 1)
 * @method static array createRandomModelData()
 * @method static BaseProjectFactory withTasks(int $quantity = 1)
 * @method static BaseProjectFactory forUsers(array $users)
 * @method static BaseProjectFactory createTasks(Project $project)
 */
class ProjectFactory extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return BaseProjectFactory::class;
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
