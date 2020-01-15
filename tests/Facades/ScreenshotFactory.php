<?php

namespace Tests\Facades;

use App\Models\TimeInterval;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use phpDocumentor\Reflection\Types\Integer;
use Tests\Factories\ProjectFactory as BaseProjectFactory;
use Tests\Factories\ScreenshotFactory as BaseScreenshotFactory;
use App\Models\Screenshot;
use Illuminate\Support\Facades\Facade;

/**
 * Class ScreenshotFactory
 *
 * @method static Screenshot create(array $attributes = [])
 * @method static Collection createMany(int $amount = 1)
 * @method static array getRandomScreenshotData
 * @method static Integer getIntervalId
 * @method static UploadedFile getImage
 * @method static BaseProjectFactory setInterval(TimeInterval $interval)
 * @method static BaseProjectFactory withRandomRelations()
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
    protected static function getFacadeAccessor()
    {
        return BaseScreenshotFactory::class;
    }
}
