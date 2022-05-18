<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'full_name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'url' => '',
            'company_id' => 1,
            'avatar' => '',
            'screenshots_active' => 1,
            'manual_time' => 0,
            'computer_time_popup' => 300,
            'blur_screenshots' => 0,
            'web_and_app_monitoring' => 1,
            'screenshots_interval' => 5,
            'active' => 1,
            'password' => 'password',
            'user_language' => 'en',
            'role_id' => 2,
            'type' => 'employee',
            'last_activity' => now()->subMinutes(random_int(1, 55)),
        ];
    }

    public function admin(): UserFactory
    {
        return $this->state(fn () => ['is_admin' => true]);
    }
}
