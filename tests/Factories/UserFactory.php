<?php

namespace Tests\Factories;

use App\Models\User;
use Carbon\Carbon;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserFactory extends Factory
{
    public const USER_ROLE = 2;
    public const MANAGER_ROLE = 1;
    public const AUDITOR_ROLE = 3;

    private int $tokensAmount = 0;
    private ?int $roleId = null;

    private User $user;
    private bool $isAdmin = false;


    protected function getModelInstance(): Model
    {
        return $this->user;
    }

    public function create(): User
    {
        $modelData = $this->createRandomModelData();

        if ($this->isAdmin) {
            $modelData['is_admin'] = true;
        }
        $this->user = User::create($modelData);

        if ($this->tokensAmount) {
            $this->createTokens();
        }

        if ($this->roleId) {
            $this->assignRole();
        }

        $this->user->save();

        if ($this->timestampsHidden) {
            $this->hideTimestamps();
        }

        return $this->user;
    }


    public function createRandomModelData(): array
    {
        $faker = FakerFactory::create();

        $fullName = $faker->name;

        return [
            'full_name' => $fullName,
            'email' => $faker->unique()->safeEmail,
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
            'password' => $fullName,
            'user_language' => 'en',
            'role_id' => 2,
            'type' => 'employee',
            'nonce' => 0,
            'last_activity' => Carbon::now()->subMinutes(rand(1, 55)),
        ];
    }

    public function createRandomRegistrationModelData(): array
    {
        $faker = FakerFactory::create();

        return [
            'full_name' => $faker->name,
            'email' => $faker->unique()->safeEmail,
            'active' => 1,
            'password' => $faker->password,
            'screenshots_interval' => 5,
            'user_language' => 'en',
            'screenshots_active' => true,
            'computer_time_popup' => 10,
            'timezone' => 'UTC',
            'role_id' => 2,
            'type' => 'employee'
        ];
    }

    public function withTokens(int $quantity = 1): self
    {
        $this->tokensAmount = $quantity;
        return $this;
    }

    public function asAdmin(): self
    {
        $this->roleId = self::USER_ROLE;
        $this->isAdmin = true;
        return $this;
    }

    public function asManager(): self
    {
        $this->roleId = self::MANAGER_ROLE;
        return $this;
    }

    public function asAuditor(): self
    {
        $this->roleId = self::AUDITOR_ROLE;
        return $this;
    }

    public function asUser(): self
    {
        $this->roleId = self::USER_ROLE;
        return $this;
    }

    protected function createTokens(): void
    {
        $tokens = array_map(fn() => [
            'token' => JWTAuth::fromUser($this->user),
            'expires_at' => now()->addDay()
        ], range(0, $this->tokensAmount));

        cache(["testing:{$this->user->id}:tokens" => $tokens]);
    }

    protected function assignRole(): void
    {
        $this->user->role_id = $this->roleId;
    }
}
