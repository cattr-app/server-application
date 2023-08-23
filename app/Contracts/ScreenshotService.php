<?php

namespace App\Contracts;

use App\Jobs\GenerateScreenshotThumbnail;
use App\Models\TimeInterval;
use Image;
use Intervention\Image\Constraint;
use Storage;

abstract class ScreenshotService
{
    protected const FILE_FORMAT = 'jpg';
    public const PARENT_FOLDER = 'screenshots/';
    public const THUMBS_FOLDER = 'thumbs/';
    private const THUMB_WIDTH = 280;
    private const QUALITY = 50;

    /** Get screenshot path by interval */
    abstract public function getScreenshotPath(TimeInterval|int $interval): string;
    /** Get screenshot thumbnail path by interval */
    abstract public function getThumbPath(TimeInterval|int $interval): string;

    public function saveScreenshot($file, $timeInterval): void
    {
        if (!Storage::exists(self::PARENT_FOLDER)) {
            Storage::makeDirectory(self::PARENT_FOLDER);
        }

        $path = is_string($file) ? $file : $file->path();

        $image = Image::make($path);

        Storage::put($this->getScreenshotPath($timeInterval), (string)$image->encode(self::FILE_FORMAT, self::QUALITY));

        GenerateScreenshotThumbnail::dispatch($timeInterval);
    }

    public function createThumbnail(TimeInterval|int $timeInterval): void
    {
        if (!Storage::exists(self::PARENT_FOLDER . self::THUMBS_FOLDER)) {
            Storage::makeDirectory(self::PARENT_FOLDER . self::THUMBS_FOLDER);
        }

        $image = Image::make(Storage::path($this->getScreenshotPath($timeInterval)));

        $thumb = $image->resize(self::THUMB_WIDTH, null, fn(Constraint $constraint) => $constraint->aspectRatio());

        Storage::put($this->getThumbPath($timeInterval), (string)$thumb->encode(self::FILE_FORMAT, self::QUALITY));
    }

    public function destroyScreenshot(TimeInterval|int $interval): void
    {
        Storage::delete($this->getScreenshotPath($interval));
        Storage::delete($this->getThumbPath($interval));
    }

    public static function getFullPath(): string
    {
        $fileSystemPath = config('filesystems.default');
        return storage_path(config("filesystems.disks.$fileSystemPath.root"));
    }
}
