<?php

namespace Tests\Feature\Auth\PasswordReset;

use App\Models\User;
use DB;
use Hash;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ValidateTest extends TestCase
{
    private const URI = 'auth/password/reset/validate';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::create();
    }

    protected function createReset(string $email, string $token, string $createdAt): array
    {
        DB::table('password_resets')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => $createdAt
        ]);

        return ['email' => $email, 'token' => $token];
    }

    public function test_validate(): void
    {
        $reset = $this->createReset($this->user->email, 'token', now());

        $response = $this->postJson(self::URI, $reset);

        $response->assertOk();
    }

    public function test_invalid_token(): void
    {
        $reset = $this->createReset($this->user->email, 'token', now());
        $reset['token'] = 'invalid_token';

        $response = $this->postJson(self::URI, $reset);

        $response->assertUnauthorized('authorization.invalid_password_data');
    }

    public function test_invalid_email(): void
    {
        $reset = $this->createReset($this->user->email, 'token', now());
        $reset['email'] = 'invalidemail@example.com';

        $response = $this->postJson(self::URI, $reset);

        $response->assertUnauthorized('authorization.invalid_password_data');
    }

    public function test_almost_expired(): void
    {
        $reset = [$this->user->email, 'expired', now()->subMinutes(config('auth.passwords.users.expire') - 1)];
        $reset = $this->createReset(...$reset);

        $response = $this->postJson(self::URI, $reset);

        $response->assertOk();
    }

    public function test_expired(): void
    {
        $reset = [$this->user->email, 'expired', now()->subMinutes(config('auth.passwords.users.expire'))];
        $reset = $this->createReset(...$reset);

        $response = $this->postJson(self::URI, $reset);

        $response->assertUnauthorized('authorization.invalid_password_data');
    }

    public function test_without_params(): void
    {
        $response = $this->postJson(self::URI);
        $response->assertError(self::HTTP_BAD_REQUEST);
    }
}
