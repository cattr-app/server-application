<?php

namespace Tests\Factories;

use App\Models\Screenshot;
use App\Models\TimeInterval;
use Faker\Factory as FakerFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use \Tests\Facades\IntervalFactory;

/**
 * Class ScreenshotFactory
 */
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

    /**
     * @var bool
     */
    private $fakeStorage = false;

    /**
     * @return $this
     */
    public function fake(): self
    {
        $this->fakeStorage = true;

        return $this;
    }

    /**
     * @return array
     */
    private function generateScreenshotData(): array
    {
        $name = FakerFactory::create()->unique()->firstName . '.jpg';
        $image = UploadedFile::fake()->image($name);

        $path = Storage::put('uploads/screenshots', $image);
        $thumbnail = Storage::put('uploads/screenshots', UploadedFile::fake()->image($name));

        return compact('path', 'thumbnail');
    }

    /**
     * @param array $attributes
     * @return Screenshot
     */
    public function create(array $attributes = []): Screenshot
    {
        if ($this->fakeStorage) {
            Storage::fake();
        }

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

    /**
     * @param Screenshot $screenshot
     */
    private function defineInterval(Screenshot $screenshot): void
    {
        if ($this->randomRelations || !$this->interval) {
            $this->interval = IntervalFactory::create();
        }

        $screenshot->time_interval_id = $this->interval->id;
    }
}
