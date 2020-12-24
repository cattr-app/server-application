<?php

namespace App\Helpers;

use App\Models\Screenshot;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

class StorageCleanerHelper
{
    private const SCREENSHOTS_PATH = 'app/uploads/screenshots';
    private const THUMBNAILS_PATH = self::SCREENSHOTS_PATH . '/thumbs';

    public static function getFreeSpace(): float
    {
        return disk_free_space(storage_path(self::SCREENSHOTS_PATH));
    }

    public static function getUsedSpace(): float
    {
        return config('cleaner.total_space') - self::getFreeSpace();
    }

    public static function getPath($scope = 'screenshots'): ?string
    {
        switch ($scope) {
            case 'screenshots':
                $path = self::SCREENSHOTS_PATH;
                break;
            case 'thumbnails':
                $path = self::THUMBNAILS_PATH;
                break;
            default:
                return null;
        }

        if (!file_exists(storage_path($path))
            && !mkdir($concurrentDirectory = storage_path($path), 0777, true)
            && !is_dir($concurrentDirectory)
        ) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
        }

        return $path;
    }

    public static function needThinning(): bool
    {
        return
            self::getUsedSpace() * 100 / config('cleaner.total_space')
            >= config('cleaner.threshold');
    }

    public static function thin($force = false): void
    {
        if ((!$force && !self::needThinning()) || cache('thinning_now')) {
            return;
        }

        cache(['thinning_now' => true]);

        while (self::getUsedSpace() > self::getWaterlineBorder()) {
            $availableScreenshots = self::getAvailableScreenshots();

            if (count($availableScreenshots) === 0) {
                break;
            }

            foreach ($availableScreenshots as $screenshot) {
                $screenshot->delete();
            }
        }

        cache(['thinning_now' => false]);
        cache(['last_thin' => now()]);
    }

    public static function getAvailableScreenshots(): object
    {
        return self::getScreenshotsCollection()->limit(config('cleaner.page_size'))->get();
    }

    public static function countAvailableScreenshots(): int
    {
        return self::getScreenshotsCollection()->count();
    }

    private static function getWaterlineBorder(): float
    {
        return config('cleaner.total_space')
            * (config('cleaner.threshold') * 0.01)
            * (1 - config('cleaner.waterline') * 0.01);
    }

    private static function getScreenshotsCollection()
    {
        return Screenshot::whereHas('timeInterval.task', function (Builder $query) {
            $query->where('important', '=', 0);
        })
            ->whereHas('timeInterval.task.project', function (Builder $query) {
                $query->where('important', '=', 0);
            })->whereHas('timeInterval.user', function (Builder $query) {
                $query->where('permanent_screenshots', '=', 0);
            })->orderBy('id');
    }
}
