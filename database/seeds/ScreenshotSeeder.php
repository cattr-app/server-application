<?php

use Illuminate\Database\Seeder;
use Intervention\Image\Constraint;

class ScreenshotSeeder extends Seeder
{
    private const SCREENSHOT_WIDTH = 1920;
    private const SCREENSHOT_HEIGHT = 1080;
    private const SCREENSHOTS_AMOUNT = 10;

    /**
     * Run the database seeds.
     * @throws Exception
     */
    public function run(): void
    {
        for ($i = 0; $i < self::SCREENSHOTS_AMOUNT; $i++) {
            $this->createScreenshot();
        }
    }

    /**
     * @throws Exception
     */
    public function createScreenshot(): string
    {
        if (!Storage::exists('uploads/screenshots/thumbs')) {
            Storage::makeDirectory('uploads/screenshots/thumbs');
        }

        $image = imagecreatetruecolor(self::SCREENSHOT_WIDTH, self::SCREENSHOT_HEIGHT);
        $background = imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255));

        imagefill($image, 0, 0, $background);

        $fileName = uniqid('demo', true);
        $path = "uploads/screenshots/$fileName.jpg";
        $absolutePath = Storage::disk()->path($path);

        imagejpeg($image, $absolutePath);
        imagedestroy($image);

        $thumbnailPath = str_replace('uploads/screenshots', 'uploads/screenshots/thumbs', $path);
        $screenshot = Image::make($absolutePath);
        $thumbnail = $screenshot->resize(280, null, fn(Constraint $constraint) => $constraint->aspectRatio());

        Storage::put($thumbnailPath, (string)$thumbnail->encode());

        return $path;
    }
}
