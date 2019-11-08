<?php

namespace App\Models\Factories;

use Faker\Generator as Faker;
use App\User;
use JWTAuth;


class UserFactory
{
    /** @var Faker $faker */
    protected $faker;

    /** @var bool $needsToken*/
    protected $needsToken;

    public function __construct(Faker $faker)
    {
        $this->faker = $faker;
    }

    public function getRandomUserData()
    {
        $full_name = $this->faker->name;

        return [
            'full_name' => $full_name,
            'email' => $this->faker->unique()->safeEmail,
            'url' => '',
            'company_id' => 1,
            'payroll_access' => 0,
            'billing_access' => 0,
            'avatar' => '',
            'screenshots_active' => 1,
            'manual_time' => 0,
            'permanent_tasks' => 0,
            'computer_time_popup' => 300,
            'poor_time_popup' => '',
            'blur_screenshots' => 0,
            'web_and_app_monitoring' => 1,
            'webcam_shots' => 0,
            'screenshots_interval' => 5,
            'active' => 1,
            'password' => bcrypt($full_name),
        ];
    }

    public function withToken()
    {
        $this->needsToken = true;
        return $this;
    }

    public function create()
    {
        /** @var User $user */
        $user = factory(User::class)->create();

        if ($this->needsToken) {
            $user->tokens()->create([
                'token' => JWTAuth::fromUser($user),
                'expires_at' => now()->addDay()
            ]);
        }

        return $user;
    }
}
