<?php

namespace App\Helpers;

use App\Contracts\ScreenshotService;
use App\Models\TimeInterval;
use Faker\Factory;

class FakeScreenshotGenerator
{
    private const SCREENSHOT_WIDTH = 1920;
    private const SCREENSHOT_HEIGHT = 1080;

    public static function runForTimeInterval(TimeInterval|int $timeInterval): void
    {
        $service = app()->make(ScreenshotService::class);

        $tmpFile = tempnam(sys_get_temp_dir(), 'cattr_screenshot');

        $image = imagecreatetruecolor(self::SCREENSHOT_WIDTH, self::SCREENSHOT_HEIGHT);
        $background = imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255));

        imagefill($image, 0, 0, $background);

        \imagejpeg($image, $tmpFile);
        imagedestroy($image);

        $service->saveScreenshot(
            $tmpFile,
            $timeInterval
        );

        unlink($tmpFile);
    }
}
