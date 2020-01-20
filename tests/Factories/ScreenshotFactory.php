<?php

namespace Tests\Factories;

use App\Models\Screenshot;
use App\Models\TimeInterval;
use Faker\Factory as FakerFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use \Tests\Facades\IntervalFactory;

class ScreenshotFactory extends AbstractFactory
{
    /**
     * @var TimeInterval
     */
    private $interval;

    /**
     * @var bool
     */
    private $randomRelations = false;

    private function generateScreenshotData(): array
    {
        $name = FakerFactory::create()->unique()->firstName . '.jpg';
        $image = UploadedFile::fake()->image($name);

        $path = Storage::put('uploads/screenshots', $image);
        $thumbnail = Storage::put('uploads/screenshots', UploadedFile::fake()->image($name));

        return compact('path', 'thumbnail');
    }

    public function create(array $attributes = []): Screenshot
    {
        $screenshotData = $this->generateScreenshotData();

        if ($attributes) {
            $screenshotData = array_merge($screenshotData, $attributes);
        }

        $screenshot = Screenshot::make($screenshotData);

        $this->defineInterval($screenshot);
        $screenshot->save();

        if ($this->timestampsHidden) {
            $this->hideTimestamps($screenshot);
        }

        return $screenshot;
    }

    /**
     * @return self
     */
    public function withRandomRelations(): self
    {
        $this->randomRelations = true;

        return $this;
    }

    /**
     * @param TimeInterval $interval
     * @return self
     */
    public function forInterval(TimeInterval $interval): self
    {
        $this->interval = $interval;

        return $this;
    }

    private function defineInterval(Screenshot $screenshot): void
    {
        if ($this->randomRelations || !$this->interval) {
            $this->interval = IntervalFactory::create();
        }

        $screenshot->time_interval_id = $this->interval->id;
    }
}
