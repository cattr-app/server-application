<?php

namespace App\Services;

use App\Contracts\ScreenshotService as ScreenshotServiceContract;
use App\Models\TimeInterval;

class ProductionScreenshotService extends ScreenshotServiceContract
{
    public function getScreenshotPath(TimeInterval|int $interval): string
    {
        return self::PARENT_FOLDER . hash('sha256', optional($interval)->id ?: $interval) . '.' . self::FILE_FORMAT;
    }

    public function getThumbPath(TimeInterval|int $interval): string
    {
        return self::PARENT_FOLDER . self::THUMBS_FOLDER . hash(
            'sha256',
            optional($interval)->id ?: $interval
        ) . '.' . self::FILE_FORMAT;
    }
}
