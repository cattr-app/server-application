<?php

namespace Tests\Facades;

use App\Models\ProjectsUsers;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Tests\Factories\ProjectUserFactory as BaseProjectUserFactory;

/**
 * @method static  ProjectsUsers create(array $attributes = [])
 * @method static BaseProjectUserFactory forUser(User $user)
 * @method static BaseProjectUserFactory setRole(int $roleId)
 * @method static Collection createMany(int $amount = 1)
 * @method static array createRandomModelData()
 */
class ProjectUserFactory extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return BaseProjectUserFactory::class;
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
