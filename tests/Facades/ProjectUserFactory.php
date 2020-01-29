<?php

namespace Tests\Facades;

use App\Models\ProjectsUsers;
use Tests\Factories\ProjectUserFactory as BaseProjectUserFactory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static  ProjectsUsers create(array $attributes = [])
 * @method static Collection createMany(int $amount = 1)
 * @method static array generateProjectUserData()
 */
class ProjectUserFactory extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return BaseProjectUserFactory::class;
    }
}
