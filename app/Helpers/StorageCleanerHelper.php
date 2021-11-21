<?php

namespace App\Helpers;

use App\Contracts\ScreenshotService;
use App\Models\TimeInterval;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Storage;

class StorageCleanerHelper
{

    public static function getFreeSpace(): float
    {
        return disk_free_space(Storage::path(ScreenshotService::PARENT_FOLDER));
    }

    public static function getUsedSpace(): float
    {
        return config('cleaner.total_space') - self::getFreeSpace();
    }

    public static function needThinning(): bool
    {
        return
            self::getUsedSpace() * 100 / config('cleaner.total_space')
            >= config('cleaner.threshold');
    }

    /**
     * @throws BindingResolutionException
     */
    public static function thin($force = false): void
    {
        if ((!$force && !self::needThinning()) || cache('thinning_now')) {
            return;
        }

        cache(['thinning_now' => true]);

        $service = app()->make(ScreenshotService::class);

        while (self::getUsedSpace() > self::getWaterlineBorder()) {
            $availableScreenshots = self::getAvailableScreenshots();

            if (count($availableScreenshots) === 0) {
                break;
            }

            foreach ($availableScreenshots as $screenshot) {
                $service->destroyScreenshot($screenshot);
            }
        }

        cache(['thinning_now' => false]);
        cache(['last_thin' => now()]);
    }

    /**
     * @return array
     * @throws BindingResolutionException
     */
    public static function getAvailableScreenshots(): array
    {
        $service = app()->make(ScreenshotService::class);

        $collection = self::getScreenshotsCollection();

        $result = [];
        $i = 0;

        foreach ($collection->cursor() as $interval) {
            if (Storage::exists($service->getScreenshotPath($interval))) {
                $result[] = $interval->id;
            }

            if ($i >= config('cleaner.page_size')) {
                break;
            }
        }

        return $result;
    }

    /**
     * @return int
     * @throws BindingResolutionException
     */
    public static function countAvailableScreenshots(): int
    {
        $count = 0;

        $service = app()->make(ScreenshotService::class);

        $collection = self::getScreenshotsCollection();

        foreach ($collection->cursor() as $interval) {
            $count += (int)Storage::exists($service->getScreenshotPath($interval));
        }

        return $count;
    }

    private static function getWaterlineBorder(): float
    {
        return config('cleaner.total_space')
            * (config('cleaner.threshold') * 0.01)
            * (1 - config('cleaner.waterline') * 0.01);
    }

    private static function getScreenshotsCollection(): Builder|TimeInterval|\Illuminate\Database\Query\Builder
    {
        return TimeInterval::whereHas('task', function (Builder $query) {
            $query->where('important', '=', 0);
        })
            ->whereHas('task.project', function (Builder $query) {
                $query->where('important', '=', 0);
            })->whereHas('user', function (Builder $query) {
                $query->where('permanent_screenshots', '=', 0);
            })->orderBy('id');
    }
}
