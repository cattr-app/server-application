<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Screenshot;

class RotateScreenshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'screenshots:rotate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate screenshots';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns absolute path for the relative to storage path.
     */
    protected function getAbsolutePath(string $path) : string
    {
        $path = Storage::disk()->path($path);
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        return $path;
    }

    /**
     * Returns disk space usage in bytes for a file or directory.
     */
    protected function getDiskUsage(string $path) : int
    {
        $io = popen("du -sk $path", 'r');
        $used = fgets($io, 4096);
        // du returns size in kilobytes
        $used = (int)substr($used, 0, strpos($used, "\t")) * 1024;
        pclose($io);

        return $used;
    }

    /**
     * Returns waterline in bytes. False if SCREENSHOTS_WATERLINE is not set.
     */
    protected function getWaterline(int $storageSize)
    {
        $value = env('SCREENSHOTS_WATERLINE', false);
        if ($value === false) {
            $this->error("SCREENSHOTS_WATERLINE is not set");
            return false;
        }

        $value = trim($value);
        if (substr($value, -1) === '%') {
            // Calculate percent of storage size
            $value = substr($value, 0, strlen($value) - 1);
            $value = floor($storageSize * $value / 100);
        } else {
            // Convert from megabytes
            $value = (int)$value * 1024 * 1024;
        }

        return $value;
    }

    /**
     * Returns rotation step in bytes. False if SCREENSHOTS_ROTATION_STEP is not set.
     */
    protected function getRotationStep(int $storageSize)
    {
        $value = env('SCREENSHOTS_ROTATION_STEP', false);
        if ($value === false) {
            $this->error("SCREENSHOTS_ROTATION_STEP is not set");
            return false;
        }

        $value = trim($value);
        if (substr($value, -1) === '%') {
            // Calculate percent of storage size
            $value = substr($value, 0, strlen($value) - 1);
            $value = floor($storageSize * $value / 100);
        } else {
            // Convert from megabytes
            $value = (int)$value * 1024 * 1024;
        }

        return $value;
    }

    /**
     * Returns array of screenshots to delete.
     */
    protected function getScreenshotsToDelete(int $count) : array
    {
        $screenshots = \DB::table('screenshots')
            ->leftJoin('time_intervals', 'screenshots.time_interval_id', '=', 'time_intervals.id')
            ->leftJoin('users', 'time_intervals.user_id', '=', 'users.id')
            ->leftJoin('tasks', 'time_intervals.task_id', '=', 'tasks.id')
            ->leftJoin('projects', 'tasks.project_id', '=', 'projects.id')
            ->where('screenshots.is_removed', false)
            ->where('screenshots.important', false)
            ->where('users.important', false)
            ->where('tasks.important', false)
            ->where('projects.important', false)
            ->orderBy('id')
            ->take($count)
            ->get(['screenshots.id', 'screenshots.path', 'screenshots.thumbnail_path'])
            ->toArray();

        return array_map(function ($screenshot) {
            $size = 0;

            if (!empty($screenshot->path)) {
                $path = $this->getAbsolutePath($screenshot->path);
                if (file_exists($path)) {
                    $size += $this->getDiskUsage($path);
                }
            } else {
                $path = null;
            }

            if (!empty($screenshot->thumbnail_path)) {
                $thumb_path = $this->getAbsolutePath($screenshot->thumbnail_path);
                if (file_exists($thumb_path)) {
                    $size += $this->getDiskUsage($thumb_path);
                }
            } else {
                $thumb_path = null;
            }

            return [
                'id' => $screenshot->id,
                'path' => $path,
                'thumb_path' => $thumb_path,
                'size' => $size,
            ];
        }, $screenshots);
    }

    /**
     * Removes files related to a screenshot.
     */
    protected function removeScreenshotFiles($screenshot)
    {
        if (file_exists($screenshot['path'])) {
            unlink($screenshot['path']);
        }

        if (file_exists($screenshot['thumb_path'])) {
            unlink($screenshot['thumb_path']);
        }
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $path = $this->getAbsolutePath('uploads/screenshots');
        $storageSize = (int)env('SCREENSHOTS_STORAGE_SIZE', -1) * 1024 * 1024;
        if ($storageSize < 0) {
            $storageSize = disk_total_space($path);
            $spaceFree = disk_free_space($path);
        } else {
            $spaceUsed = $this->getDiskUsage($path);
            $spaceFree = $storageSize - $spaceUsed;
        }

        $waterline = $this->getWaterline($storageSize);
        if ($waterline === false) {
            return;
        }

        $rotationStep = $this->getRotationStep($storageSize);
        if ($rotationStep === false) {
            return;
        }

        if ($spaceFree > $waterline) {
            return;
        }

        $spaceRequired = $rotationStep - $spaceFree;
        $count = (int)env('SCREENSHOTS_ROTATION_COUNT_PER_ITER', 10);
        while ($spaceRequired > 0) {
            $screenshots = $this->getScreenshotsToDelete($count);
            if (empty($screenshots)) {
                break;
            }

            $iterationFreed = array_reduce($screenshots, function ($total, $screenshot) {
                return $total + $screenshot['size'];
            }, 0);

            if ($iterationFreed < $spaceRequired) {
                foreach ($screenshots as $screenshot) {
                    $this->removeScreenshotFiles($screenshot);
                }

                $ids = array_reduce($screenshots, function ($ids, $screenshot) {
                    $ids[] = $screenshot['id'];
                    return $ids;
                }, []);

                \DB::table('screenshots')
                    ->whereIn('id', $ids)
                    ->update(['is_removed' => true]);

                $spaceRequired -= $iterationFreed;
            } else {
                foreach ($screenshots as $screenshot) {
                    $this->removeScreenshotFiles($screenshot);

                    \DB::table('screenshots')
                        ->where('id', $screenshot['id'])
                        ->update(['is_removed' => true]);

                    $spaceRequired -= $screenshot['size'];
                    if ($spaceRequired < 0) {
                        break;
                    }
                }
            }
        }
    }
}
