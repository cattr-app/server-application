<?php

namespace App\Helpers;

use App\Contracts\ScreenshotService;
use App\Models\TimeInterval;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Builder;
use Storage;

/**
 * Screenshot rotation by free space of the local disk (PVC).
 *
 * Object storage (FILESYSTEM_DISK=s3) has no "free space": rotation is a no-op there,
 * retention is expected to be handled by bucket lifecycle rules.
 */
class StorageCleaner
{
    public static function isSupported(): bool
    {
        return StorageHelper::isLocal();
    }

    public static function getFreeSpace(): float
    {
        if (!self::isSupported()) {
            return 0;
        }

        $directory = Storage::path(ScreenshotService::PARENT_FOLDER);

        return disk_free_space(is_dir($directory) ? $directory : Storage::path(''));
    }

    public static function getUsedSpace(): float
    {
        if (!self::isSupported()) {
            return 0;
        }

        return config('cleaner.total_space') - self::getFreeSpace();
    }

    public static function needThinning(): bool
    {
        if (!self::isSupported()) {
            return false;
        }

        return
            self::getUsedSpace() * 100 / config('cleaner.total_space')
            >= config('cleaner.threshold');
    }

    /**
     * Removes screenshots until the used space drops below the waterline.
     *
     * Not guarded against concurrent runs: cattr:screenshots:rotate holds StorageCleanupStatus::runLock().
     *
     * @param callable|null $heartbeat called after every removed page of screenshots
     * @throws BindingResolutionException
     */
    public static function thin($force = false, ?callable $heartbeat = null): void
    {
        if (!self::isSupported() || (!$force && !self::needThinning())) {
            return;
        }

        $service = app()->make(ScreenshotService::class);

        while (self::getUsedSpace() > self::getWaterlineBorder()) {
            $availableScreenshots = self::getAvailableScreenshots();

            if (count($availableScreenshots) === 0) {
                break;
            }

            foreach ($availableScreenshots as $screenshot) {
                $service->destroyScreenshot($screenshot);
            }

            if ($heartbeat) {
                $heartbeat();
            }
        }
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
                $i++;
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
        // One existence check per interval is fine on a local disk, but would be a HEAD request each on S3.
        if (!self::isSupported()) {
            return 0;
        }

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
