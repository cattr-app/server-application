<?php

namespace App\Services;

use App\Contracts\WebcamScreenshotService as WebcamScreenshotServiceContract;
use App\Models\TimeInterval;

class ProductionWebcamScreenshotService extends WebcamScreenshotServiceContract
{
    public function getWebcamPath(TimeInterval|int $interval): string
    {
        return self::PARENT_FOLDER . hash('sha256', optional($interval)->id ?: $interval) . '.' . self::FILE_FORMAT;
    }

    public function getWebcamThumbPath(TimeInterval|int $interval): string
    {
        return self::PARENT_FOLDER . self::THUMBS_FOLDER . hash(
            'sha256',
            optional($interval)->id ?: $interval
        ) . '.' . self::FILE_FORMAT;
    }
}
