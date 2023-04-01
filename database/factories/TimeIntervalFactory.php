<?php

namespace Database\Factories;

use App\Helpers\FakeScreenshotGenerator;
use App\Models\TimeInterval;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeIntervalFactory extends Factory
{
    protected $model = TimeInterval::class;

    public function definition(): array
    {
        return [
            'is_manual' => false,
            'start_at' => now()->subMinutes(5)->toDateTimeString(),
            'end_at' => now()->toDateTimeString(),
            'mouse_fill' => fake()->numberBetween(0, 100),
            'keyboard_fill' => fake()->numberBetween(0, 100),
            'activity_fill' => static fn(array $attributes) =>
                +$attributes['keyboard_fill'] + $attributes['mouse_fill'],
        ];
    }

    public function withScreenshot(): TimeIntervalFactory
    {
        return $this->afterCreating(function (TimeInterval $timeInterval) {
            FakeScreenshotGenerator::runForTimeInterval($timeInterval);
        });
    }
}
