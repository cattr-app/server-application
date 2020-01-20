<?php

namespace Tests\Facades;

use App\Models\TimeInterval;
use Illuminate\Support\Collection;
use Tests\Factories\ProjectFactory as BaseProjectFactory;
use Tests\Factories\ScreenshotFactory as BaseScreenshotFactory;
use App\Models\Screenshot;
use Illuminate\Support\Facades\Facade;

/**
 * Class ScreenshotFactory
 *
 * @method static Screenshot create(array $attributes = [])
 * @method static Collection createMany(int $amount = 1)
 * @method static BaseProjectFactory withRandomRelations()
 * @method static BaseProjectFactory forInterval(TimeInterval $interval)
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
