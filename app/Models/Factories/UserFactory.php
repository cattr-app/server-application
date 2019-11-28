<?php

namespace App\Models\Factories;

use Faker\Generator as Faker;
use App\User;
use JWTAuth;

/**
 * Class UserFactory
 * @package App\Models\Factories
 */
class UserFactory
{
    private const ROLES = [
        'admin' => 1,
        'user' => 2
    ];

    /** @var Faker $faker */
    protected $faker;

    /** @var int $needsToken */
    protected $needsTokens = 0;

    /** @var User $user */
    protected $user;

    /** @var string */
    protected $role;

    /**
     * UserFactory constructor.
     * @param Faker $faker
     */
    public function __construct(Faker $faker)
    {
        $this->faker = $faker;
        $this->user = User::make($this->getRandomUserData());
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
     * @param int $quantity
     * @return $this
     */
    public function withTokens(int $quantity = 1): self
    {
        $this->needsTokens = $quantity;
        return $this;
    }

    /**
     * @return $this
     */
    public function asAdmin(): self
    {
        $this->role = 'admin';

        return $this;
    }

    /**
     * @return $this
     */
    public function asUser(): self
    {
        $this->role = 'user';

        return $this;
    }

    /**
     * @return User
     */
    public function create(): User
    {
        $this->user->save();

        if ($this->needsTokens) {
            $this->createTokens();
        }

        if (!is_null($this->role)) {
            $this->assignRole();
        }

        return $this->user;
    }

    protected function assignRole(): void
    {
        $this->user->attachRole(self::ROLES[$this->role]);
    }

    protected function createTokens(): void
    {
        $tokens = [];

        while ($this->needsTokens--) {
            $tokens[] = [
                'token' => JWTAuth::fromUser($this->user),
                'expires_at' => now()->addDay()
            ];
        }

        $this->user->tokens()->createMany($tokens);
    }
}
