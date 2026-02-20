<?php

namespace App\Contracts;

use App\Jobs\GenerateWebcamThumbnail;
use App\Models\TimeInterval;
use Image;
use Intervention\Image\Constraint;
use Storage;

abstract class WebcamScreenshotService
{
    protected const FILE_FORMAT = 'jpg';
    public const PARENT_FOLDER = 'webcam/';
    public const THUMBS_FOLDER = 'thumbs/';
    private const THUMB_WIDTH = 280;
    private const QUALITY = 50;

    /** Get webcam screenshot path by interval */
    abstract public function getWebcamPath(TimeInterval|int $interval): string;
    /** Get webcam screenshot thumbnail path by interval */
    abstract public function getWebcamThumbPath(TimeInterval|int $interval): string;

    public function saveWebcamScreenshot($file, TimeInterval $timeInterval): void
    {
        if (!Storage::exists(self::PARENT_FOLDER)) {
            Storage::makeDirectory(self::PARENT_FOLDER);
        }

        $path = is_string($file) ? $file : $file->path();

        $image = Image::make($path);

        Storage::put($this->getWebcamPath($timeInterval), (string)$image->encode(self::FILE_FORMAT, self::QUALITY));

        GenerateWebcamThumbnail::dispatch($timeInterval);
    }

    public function createWebcamThumbnail(TimeInterval|int $timeInterval): void
    {
        if (!Storage::exists(self::PARENT_FOLDER . self::THUMBS_FOLDER)) {
            Storage::makeDirectory(self::PARENT_FOLDER . self::THUMBS_FOLDER);
        }

        $image = Image::make(Storage::path($this->getWebcamPath($timeInterval)));

        $thumb = $image->resize(self::THUMB_WIDTH, null, fn(Constraint $constraint) => $constraint->aspectRatio());

        Storage::put($this->getWebcamThumbPath($timeInterval), (string)$thumb->encode(self::FILE_FORMAT, self::QUALITY));
    }

    public function destroyWebcamScreenshot(TimeInterval|int $interval): void
    {
        Storage::delete($this->getWebcamPath($interval));
        Storage::delete($this->getWebcamThumbPath($interval));
    }

    public static function getFullPath(): string
    {
        $fileSystemPath = config('filesystems.default');
        return storage_path(config("filesystems.disks.$fileSystemPath.root"));
    }
}
