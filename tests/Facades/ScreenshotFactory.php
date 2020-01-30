<?php

namespace Tests\Facades;

use App\Models\Screenshot;
use App\Models\TimeInterval;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Tests\Factories\ScreenshotFactory as BaseScreenshotFactory;

/**
 * Class ScreenshotFactory
 *
 * @method static Screenshot create(array $attributes = [])
 * @method static Collection createMany(int $amount = 1)
 * @method static BaseScreenshotFactory withRandomRelations()
 * @method static BaseScreenshotFactory forInterval(TimeInterval $interval)
 * @method static BaseScreenshotFactory fake()
 *
 * @mixin ScreenshotFactory
 */
class ScreenshotFactory extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return BaseScreenshotFactory::class;
    }
}
