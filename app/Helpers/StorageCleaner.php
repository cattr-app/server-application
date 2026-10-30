<?php

namespace App\Helpers;

use App\Contracts\ScreenshotService;
use App\Models\TimeInterval;
use Cache;
use Carbon\CarbonInterface;
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
    private const HEARTBEAT_INTERVALS = 1000;

    /**
     * Interval id the last lookup stopped at, shared by the runs like their status.
     */
    private const SCAN_POSITION_KEY = 'storage_cleanup:scan_position';

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
     * The intervals are scanned in id order from where the previous lookup stopped, also across runs: a run
     * stopped at its deadline in a long range of intervals without screenshots must not make the next run scan
     * the same range again. At the end the scan wraps around once.
     *
     * Not guarded against concurrent runs: cattr:screenshots:rotate holds StorageCleanupStatus::runLock().
     *
     * @param callable|null $heartbeat called while screenshots are looked up and after every removed page
     * @param CarbonInterface|null $deadline no new page is looked up after it: the run lock expires and the job
     *                                       is killed after a fixed time, the next run removes the rest
     * @throws BindingResolutionException
     */
    public static function thin($force = false, ?callable $heartbeat = null, ?CarbonInterface $deadline = null): void
    {
        if (!self::isSupported() || (!$force && !self::needThinning())) {
            return;
        }

        $service = app()->make(ScreenshotService::class);

        $position = (int)Cache::get(self::SCAN_POSITION_KEY, 0);
        $wrapped = $position === 0;

        try {
            while (self::getUsedSpace() > self::getWaterlineBorder()) {
                if ($deadline?->isPast()) {
                    break;
                }

                [$availableScreenshots, $position, $exhausted] = self::findPage($position, $heartbeat, $deadline);

                foreach ($availableScreenshots as $screenshot) {
                    $service->destroyScreenshot($screenshot);
                }

                if ($heartbeat) {
                    $heartbeat();
                }

                if (!$exhausted) {
                    continue;
                }

                // The end of the intervals: start over once, the beginning may not have been scanned in this run
                $position = 0;

                if ($wrapped) {
                    break;
                }

                $wrapped = true;
            }
        } finally {
            Cache::forever(self::SCAN_POSITION_KEY, $position);
        }
    }

    /**
     * @param callable|null $heartbeat called every HEARTBEAT_INTERVALS scanned intervals: a page can take
     *                                 long to find when most intervals have no screenshot left
     * @param CarbonInterface|null $until the lookup stops there with what it has found so far
     * @return array
     * @throws BindingResolutionException
     */
    public static function getAvailableScreenshots(?callable $heartbeat = null, ?CarbonInterface $until = null): array
    {
        return self::findPage(0, $heartbeat, $until)[0];
    }

    /**
     * Looks up the next page of intervals with a screenshot after the given interval id.
     *
     * @return array{0: int[], 1: int, 2: bool} the found ids, the last scanned id (where the next lookup
     *                                          continues) and whether the scan has reached the last interval
     * @throws BindingResolutionException
     */
    private static function findPage(int $afterId, ?callable $heartbeat, ?CarbonInterface $until): array
    {
        $service = app()->make(ScreenshotService::class);

        $collection = self::getScreenshotsCollection()->where('time_intervals.id', '>', $afterId);

        $result = [];
        $scanned = 0;
        $lastId = $afterId;

        foreach ($collection->cursor() as $interval) {
            if (++$scanned % self::HEARTBEAT_INTERVALS === 0) {
                if ($until?->isPast()) {
                    return [$result, $lastId, false];
                }

                if ($heartbeat) {
                    $heartbeat();
                }
            }

            $lastId = $interval->id;

            if (Storage::exists($service->getScreenshotPath($interval))) {
                $result[] = $interval->id;
            }

            if (count($result) >= config('cleaner.page_size')) {
                return [$result, $lastId, false];
            }
        }

        return [$result, $lastId, true];
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
