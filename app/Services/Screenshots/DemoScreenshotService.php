<?php

namespace App\Services\Screenshots;

use App\Models\Screenshot;
use App\Contracts\ScreenshotService as ScreenshotServiceInterface;
use App\Models\TimeInterval;

class DemoScreenshotService extends ScreenshotServiceInterface
{
    /**
     * Get screenshot by request path.
     *
     * @param TimeInterval|int $interval
     * @return Screenshot
     */
    public function getScreenshotPath($interval): string
    {
        return self::PARENT_FOLDER . hash(
            'sha256',
            substr(optional($interval)->id ?: $interval, -1)
        ) . '.' . self::FILE_FORMAT;
    }

    /**
     * Get screenshot thumbnail by request path.
     *
     * @param TimeInterval|int $interval
     * @return Screenshot
     */
    public function getThumbPath($interval): string
    {
        return self::PARENT_FOLDER . self::THUMBS_FOLDER . hash(
            'sha256',
            substr(optional($interval)->id ?: $interval, -1)
        ) . '.' . self::FILE_FORMAT;
    }
}
