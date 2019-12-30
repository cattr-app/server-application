<?php

namespace Tests\Factories;

use App\Models\Screenshot;
use Faker\Factory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use \Tests\Facades\IntervalFactory;

class ScreenshotFactory extends AbstractFactory
{
    private $interval;

    public function create(array $attributes = []): Screenshot
    {
        $screenshotData = $this->getRandomScreenshotData();

        if ($attributes) {
            $screenshotData = array_merge($screenshotData, $attributes);
        }

        $imagePath = Factory::create()->image();

        $screenshot = Screenshot::make($screenshotData);

        $this->defineInterval($screenshot);

        $screenshot->path = $imagePath;
        $screenshot->thumbnail_path = $imagePath;
        $screenshot->save();

        return $screenshot;
    }

    public function getIntervalId(): int
    {
        if (!$this->interval) {
            $this->interval = IntervalFactory::create();
        }

        return $this->interval->id;
    }

    /**
     * @return array
     */
    public function getRandomScreenshotData(): array
    {
        return [
            'time_interval_id' => $this->getIntervalId(),
        ];
    }

    public function getImage()
    {
        Storage::fake();

        return UploadedFile::fake()->image('avatar.jpg');
    }


    private function defineInterval(Screenshot $screenshot)
    {
        if (!$this->interval) {
            $this->interval = IntervalFactory::create();
        }

        $screenshot->time_interval_id = $this->interval->id;
    }
}
