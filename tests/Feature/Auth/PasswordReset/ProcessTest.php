<?php

namespace Tests\Feature\Auth\PasswordReset;

use Tests\Factories\UserFactory;
use App\User;
use DB;
use Hash;
use Tests\TestCase;

/**
 * Class ProcessTest
 * @package Tests\Feature\Auth\PasswordReset
 */
class ProcessTest extends TestCase
{
    private const URI = 'auth/password/reset/process';

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = app(UserFactory::class)->create();
    }

    /**
     * @param $email
     * @param $token
     * @param $createdAt
     * @return array
     */
    protected function createReset($email, $token, $createdAt)
    {
        DB::table('password_resets')->insert([
            'email' => $email,
            'token' => Hash::make($token),
            'created_at' => $createdAt
        ]);

        return [
            'email' => $email,
            'token' => $token,
            'password' => 'new_password',
            'password_confirmation' => 'new_password'
        ];
    }

    public function test_process()
    {
        $reset = $this->createReset($this->user->email, 'token', now());

        $response = $this->postJson(self::URI, $reset);

        $response->assertApiSuccess();

        $this->user->refresh();

        $this->assertTrue(Hash::check($reset['password'], $this->user->password));
        $response->assertJsonStructure(['access_token']);
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

        $this->user->refresh();

        $this->assertTrue(Hash::check($reset['password'], $this->user->password));
        $response->assertJsonStructure(['access_token']);
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
