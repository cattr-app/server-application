<?php

namespace Tests\Facades;

use App\Models\Invitation;
use Illuminate\Support\Facades\Facade;
use Tests\Factories\InvitationFactory as BaseInvitationFactory;

/**
 * @method static Invitation create(array $attributes = [])
 * @method static array createRandomModelData()
 * @method static array createRequestData()
 */
class InvitationFactory extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return BaseInvitationFactory::class;
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
