<?php

namespace App\Contracts;

use Image;
use Intervention\Image\Constraint;
use Storage;

abstract class ScreenshotService
{
    protected const FILE_FORMAT = 'jpg';
    public const PARENT_FOLDER = 'screenshots/';
    public const THUMBS_FOLDER = 'thumbs/';
    private const THUMB_WIDTH = 280;

    abstract public function getScreenshotPath($interval): string;
    abstract public function getThumbPath($interval): string;

    public function saveScreenshot($file, $timeInterval): void
    {
        if (!Storage::exists(self::PARENT_FOLDER . self::THUMBS_FOLDER)) {
            Storage::makeDirectory(self::PARENT_FOLDER . self::THUMBS_FOLDER);
        }

        $image = Image::make(is_string($file) ? $file : $file->path());
        $thumb = $image->resize(self::THUMB_WIDTH, null, fn(Constraint $constraint) => $constraint->aspectRatio());

        Storage::put(static::getScreenshotPath($timeInterval), (string)$thumb->encode(self::FILE_FORMAT));
        Storage::put(static::getThumbPath($timeInterval), (string)$image->encode(self::FILE_FORMAT));
    }

    public function destroyScreenshot($interval): void
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
