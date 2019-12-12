<?php

namespace Tests\Feature\Auth\PasswordReset;

use App\Models\Factories\UserFactory;
use App\User;
use DB;
use Hash;
use Tests\TestCase;

class ValidateTest extends TestCase
{
    const URI = 'auth/password/reset/validate';

    /**
     * @var User
     */
    private $user;

    protected function setUp()
    {
        parent::setUp();

        $this->user = app(UserFactory::class)->create();

    }

    protected function createReset($email, $token, $createdAt)
    {
        DB::table('password_resets')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => $createdAt
        ]);

        return ['email' => $email, 'token' => $token];
    }

    public function test_validate()
    {
        $reset = $this->createReset($this->user->email, 'token', now());

        $response = $this->postJson(self::URI, $reset);

        $response->assertApiSuccess();
    }

    public function test_invalid_token()
    {
        $reset = $this->createReset($this->user->email, 'token', now());
        $reset['token'] = 'invalid_token';

        $response = $this->postJson(self::URI, $reset);

        $response->assertApiError(401);
    }

    public function test_invalid_email()
    {
        $reset = $this->createReset($this->user->email, 'token', now());
        $reset['email'] = 'invalidemail@example.com';

        $response = $this->postJson(self::URI, $reset);

        $response->assertApiError(401);
    }

    public function test_almost_expired()
    {
        $reset = [$this->user->email, 'expired', now()->subMinutes(config('auth.passwords.users.expire') - 1)];
        $reset = $this->createReset(...$reset);

        $response = $this->postJson(self::URI, $reset);

        $response->assertApiSuccess();
    }

    public function test_expired()
    {
        $reset = [$this->user->email, 'expired', now()->subMinutes(config('auth.passwords.users.expire'))];
        $reset = $this->createReset(...$reset);

        $response = $this->postJson(self::URI, $reset);

        $response->assertApiError(401);
    }

    public function test_without_params()
    {
        $response = $this->postJson(self::URI);
        $response->assertApiError(400);
    }
}
