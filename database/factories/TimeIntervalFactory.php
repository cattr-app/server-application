<?php

namespace Database\Factories;

use App\Models\TimeInterval;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeIntervalFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TimeInterval::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        $mouseFill = random_int(0, 100);
        $keyboardFill = random_int(0, 100 - $mouseFill);
        $activityFill = $mouseFill + $keyboardFill;

        return [
            'task_id' => 1,
            'user_id' => 1,
            'start_at' => date('Y-m-d H:i:s', now()),
            'end_at' => date('Y-m-d H:i:s', now()->addMinutes(5)),
            'activity_fill' => $activityFill,
            'mouse_fill' => $mouseFill,
            'keyboard_fill' => $keyboardFill,
        ];
    }
}
