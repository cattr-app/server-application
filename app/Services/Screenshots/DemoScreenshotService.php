<?php

namespace App\Services\Screenshots;

use App\Contracts\ScreenshotService as ScreenshotServiceInterface;
use App\Models\TimeInterval;

class DemoScreenshotService extends ScreenshotServiceInterface
{
    public function getScreenshotPath(TimeInterval|int $interval): string
    {
        return self::PARENT_FOLDER . hash(
            'sha256',
            substr(optional($interval)->id ?: $interval, -1)
        ) . '.' . self::FILE_FORMAT;
    }

    public function getThumbPath(TimeInterval|int $interval): string
    {
        return self::PARENT_FOLDER . self::THUMBS_FOLDER . hash(
            'sha256',
            substr(optional($interval)->id ?: $interval, -1)
        ) . '.' . self::FILE_FORMAT;
    }
}
