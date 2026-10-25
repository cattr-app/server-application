<?php

namespace App\Contracts;

use App\Helpers\StorageHelper;
use App\Jobs\GenerateScreenshotThumbnail;
use App\Models\TimeInterval;
use Intervention\Image\ImageManagerStatic as Image;
use Intervention\Image\Constraint;
use Storage;
use URL;

abstract class ScreenshotService
{
    protected const FILE_FORMAT = 'jpg';
    public const PARENT_FOLDER = 'screenshots/';
    public const THUMBS_FOLDER = 'thumbs/';
    private const THUMB_WIDTH = 280;
    private const QUALITY = 50;
    /** Lifetime of signed / presigned screenshot URLs */
    public const URL_TTL_MINUTES = 10;

    /** Get screenshot path by interval */
    abstract public function getScreenshotPath(TimeInterval|int $interval): string;
    /** Get screenshot thumbnail path by interval */
    abstract public function getThumbPath(TimeInterval|int $interval): string;

    public function saveScreenshot($file, $timeInterval): void
    {
        // $file is a local path or UploadedFile; the result goes to the default disk (local or S3).
        // Storage::put() creates missing directories itself, no makeDirectory() round-trips on S3.
        $path = is_string($file) ? $file : $file->path();

        $image = Image::make($path);

        Storage::put($this->getScreenshotPath($timeInterval), (string)$image->encode(self::FILE_FORMAT, self::QUALITY));

        GenerateScreenshotThumbnail::dispatch($timeInterval);
    }

    public function createThumbnail(TimeInterval|int $timeInterval): void
    {
        $image = Image::make(Storage::get($this->getScreenshotPath($timeInterval)));

        $thumb = $image->resize(self::THUMB_WIDTH, null, fn(Constraint $constraint) => $constraint->aspectRatio());

        Storage::put($this->getThumbPath($timeInterval), (string)$thumb->encode(self::FILE_FORMAT, self::QUALITY));
    }

    public function destroyScreenshot(TimeInterval|int $interval): void
    {
        Storage::delete($this->getScreenshotPath($interval));
        Storage::delete($this->getThumbPath($interval));
    }

    /**
     * Temporary URL of the screenshot (or its thumbnail) that browsers can load directly.
     *
     * Local disk: relative URL of a route protected by the "signed" middleware that streams the file.
     * S3: presigned object URL.
     */
    public function getTemporaryUrl(TimeInterval|int $interval, bool $thumbnail = false): string
    {
        $id = $interval instanceof TimeInterval ? $interval->id : $interval;
        $expiresAt = now()->addMinutes(self::URL_TTL_MINUTES);

        if (StorageHelper::isLocal()) {
            return URL::temporarySignedRoute(
                $thumbnail ? 'intervals.screenshot.thumb' : 'intervals.screenshot.original',
                $expiresAt,
                ['interval' => $id],
                absolute: false,
            );
        }

        return Storage::temporaryUrl(
            $thumbnail ? $this->getThumbPath($id) : $this->getScreenshotPath($id),
            $expiresAt,
        );
    }

    /**
     * @return array{screenshot_url: string, thumbnail_url: string, expires_in: int}
     */
    public function getTemporaryUrls(TimeInterval|int $interval): array
    {
        return [
            'screenshot_url' => $this->getTemporaryUrl($interval),
            'thumbnail_url' => $this->getTemporaryUrl($interval, true),
            'expires_in' => self::URL_TTL_MINUTES * 60,
        ];
    }

    public static function getFullPath(): string
    {
        $fileSystemPath = config('filesystems.default');
        return storage_path(config("filesystems.disks.$fileSystemPath.root"));
    }
}
