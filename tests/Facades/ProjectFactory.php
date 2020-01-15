<?php

namespace Tests\Facades;

use Tests\Factories\ProjectFactory as BaseProjectFactory;
use App\Models\Project;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static  Project create(array $attributes = [])
 * @method static Collection createMany(int $amount = 1)
 * @method static array getRandomProjectData()
 * @method static BaseProjectFactory withTasks(int $quantity = 1)
 * @method static BaseProjectFactory forUsers(array $users)
 * @method static BaseProjectFactory createTasks(Project $project)
 */
class ProjectFactory extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BaseProjectFactory::class;
    }
}
