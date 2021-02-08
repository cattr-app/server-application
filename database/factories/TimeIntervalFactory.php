<?php

namespace Database\Factories;

use App\Contracts\ScreenshotService;
use App\Models\TimeInterval;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeIntervalFactory extends Factory
{
    protected $model = TimeInterval::class;

    public function definition(): array
    {
        return [
            'start_at' => now()->subMinutes(5)->toDateTimeString(),
            'end_at' => now()->toDateTimeString(),
            'mouse_fill' => $this->faker->numberBetween(0, 100),
            'keyboard_fill' => $this->faker->numberBetween(0, 100),
            'activity_fill' => function (array $attributes) {
                return +$attributes['keyboard_fill'] + $attributes['mouse_fill'];
            },
        ];
    }

    public function configure(): TimeIntervalFactory
    {
        return $this->afterCreating(function (TimeInterval $timeInterval) {
            $image = imagecreatetruecolor(1920, 1080);
            $background = imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255));

            imagefill($image, 0, 0, $background);

            $tmpFile = tmpfile();
            $filePath = stream_get_meta_data($tmpFile)['uri'];

            imagejpeg($image, $filePath);
            imagedestroy($image);

            app()->make(ScreenshotService::class)->saveScreenshot(
                $filePath,
                $timeInterval
            );
        });
    }
}
