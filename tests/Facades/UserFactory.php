<?php

namespace Tests\Facades;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Tests\Factories\UserFactory as BaseUserFactory;

/**
 * @method static User create()
 * @method static Collection createMany(int $amount = 1)
 * @method static BaseUserFactory withTokens(int $amount = 1)
 * @method static BaseUserFactory asAdmin()
 * @method static BaseUserFactory asUser()
 * @method static array createRandomModelData()
 * @method static array createRandomRegistrationModelData()
 */
class UserFactory extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return BaseUserFactory::class;
    }
}
