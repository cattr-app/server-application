<?php

namespace Tests\Factories;

use App\Models\Screenshot;
use App\Models\TimeInterval;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Storage;
use Tests\Facades\IntervalFactory;

class ScreenshotFactory extends Factory
{
    private ?TimeInterval $interval = null;
    private Screenshot $screenshot;

    private bool $fakeStorage = false;

    public function fake(): self
    {
        $this->fakeStorage = true;
        return $this;
    }

    protected function getModelInstance(): Model
    {
        return $this->screenshot;
    }

    public function createRandomModelData(): array
    {
        return $this->generateScreenshotData();
    }

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
        if ($this->fakeStorage) {
            Storage::fake();
        }

        $modelData = $this->createRandomModelData();
        $this->screenshot = Screenshot::make($modelData);

        $this->defineInterval();
        $this->screenshot->save();

        if ($this->timestampsHidden) {
            $this->hideTimestamps();
        }

        return $this->screenshot;
    }

    public function withRandomRelations(): self
    {
        $this->randomRelations = true;
        return $this;
    }

    public function forInterval(TimeInterval $interval): self
    {
        $this->interval = $interval;
        return $this;
    }

    private function defineInterval(): void
    {
        if ($this->randomRelations || !$this->interval) {
            $this->interval = IntervalFactory::create();
        }

        $this->screenshot->time_interval_id = $this->interval->id;
    }
}
