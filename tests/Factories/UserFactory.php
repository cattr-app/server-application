<?php

namespace Tests\Factories;

use App\Models\User;
use Faker\Factory as FakerFactory;
use JWTAuth;

/**
 * Class UserFactory
 */
class UserFactory extends AbstractFactory
{
    private const ROLES = [
        'user' => 1,
    ];

    /**
     * @var int
     */
    protected $needsTokens = 0;

    /**
     * @var string
     */
    protected $role;

    /**
     * @var bool
     */
    private $isAdmin;

    /**
     * @return array
     */
    public function getRegistrationUserData(): array
    {
        $faker = FakerFactory::create();

        return [
            'full_name' => $faker->name,
            'email' => $faker->unique()->safeEmail,
            'active' => 1,
            'password' => $faker->password
        ];
    }

    /**
     * @return array
     */
    public function getRandomUserData(): array
    {
        $faker = FakerFactory::create();

        $full_name = $faker->name;

        return [
            'full_name' => $full_name,
            'email' => $faker->unique()->safeEmail,
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
     * @return self
     */
    public function withTokens(int $quantity = 1): self
    {
        $this->needsTokens = $quantity;

        return $this;
    }

    /**
     * @return self
     */
    public function asAdmin(): self
    {
        $this->role = 'user';
        $this->isAdmin = true;

        return $this;
    }

    /**
     * @return self
     */
    public function asUser(): self
    {
        $this->role = 'user';

        return $this;
    }

    /**
     * @param User $user
     */
    protected function createTokens(User $user): void
    {
        $tokens = [];

        do {
            $tokens[] = [
                'token' => JWTAuth::fromUser($user),
                'expires_at' => now()->addDay()
            ];
        } while (--$this->needsTokens);

        $user->tokens()->createMany($tokens);
    }

    /**
     * @param User $user
     */
    protected function assignRole(User $user): void
    {
        $user->role_id = self::ROLES[$this->role];
    }

    /**
     * @param array $attributes
     * @return User
     */
    public function create(array $attributes = []): User
    {
        $userData = $this->getRandomUserData();

        if ($attributes) {
            $userData = array_merge($userData, $attributes);
        }

        //TODO remove that temp fix
        if ($this->isAdmin) {
            $userData['is_admin'] = 1;
        }

        $user = User::create($userData);

        if ($this->needsTokens) {
            $this->createTokens($user);
        }

        if ($this->role) {
            $this->assignRole($user);
        }

        $user->save();

        if ($this->timestampsHidden) {
            $this->hideTimestamps($user);
        }

        return $user;
    }
}
