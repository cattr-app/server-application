<?php

namespace Tests\Factories\Facades;

use Tests\Factories\UserFactory as BaseUserFactory;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static User create()
 * @method static Collection createMany(int $amount = 1)
 * @method static BaseUserFactory withTokens(int $amount = 1)
 * @method static BaseUserFactory asAdmin()
 * @method static BaseUserFactory asUser()
 * @method static array getRandomUserData()
 * @mixin BaseUserFactory
 */
class UserFactory extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BaseUserFactory::class;
    }
}
