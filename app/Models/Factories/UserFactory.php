<?php

namespace App\Models\Factories;

use Faker\Generator as Faker;
use App\User;
use JWTAuth;


class UserFactory
{
    /** @var Faker $faker */
    protected $faker;

    /** @var int $needsToken */
    protected $needsTokens = 0;

    /**
     * UserFactory constructor.
     * @param Faker $faker
     */
    public function __construct(Faker $faker)
    {
        $this->faker = $faker;
    }

    /**
     * @return array
     */
    public function getRandomUserData(): array
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

    /**
     * @param $user
     * @return User
     */
    protected function createTokens($user): User
    {
        $tokens = [];

        while ($this->needsTokens--) {
            $tokens[] = [
                'token' => JWTAuth::fromUser($user),
                'expires_at' => now()->addDay()
            ];
        }

        /** @var User $user */
        $user->tokens()->createMany($tokens);

        return $user;
    }

    /**
     * @param int $quantity
     * @return $this
     */
    public function withTokens(int $quantity = 1): self
    {
        $this->needsTokens = $quantity;

        return $this;
    }

    /**
     * @return User
     */
    public function create(): User
    {
        /** @var User $user */
        $user = factory(User::class)->create();
        if (!$this->needsTokens) {
            return $user;
        }

        return $this->createTokens($user);
    }
}
