<?php

namespace Tests\Facades;

use App\Models\Screenshot;
use App\Models\TimeInterval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Tests\Factories\ScreenshotFactory as BaseScreenshotFactory;

/**
 * @method static Screenshot create(array $attributes = [])
 * @method static array createRandomModelData()
 * @method static Collection createMany(int $amount = 1)
 * @method static BaseScreenshotFactory withRandomRelations()
 * @method static BaseScreenshotFactory forInterval(TimeInterval $interval)
 * @method static BaseScreenshotFactory fake()
 */
class ScreenshotFactory extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return BaseScreenshotFactory::class;
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
