<?php

namespace Tests\Feature\Auth\PasswordReset;

use Tests\Facades\UserFactory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Class ProcessTest
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

        $this->user = UserFactory::create();
    }

    /**
     * @param $email
     * @param $token
     * @param $createdAt
     * @return array
     */
    protected function createReset($email, $token, $createdAt): array
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

    public function test_process(): void
    {
        $reset = $this->createReset($this->user->email, 'token', now());

        $response = $this->postJson(self::URI, $reset);

        $response->assertSuccess();

        $this->user->refresh();

        $this->assertTrue(Hash::check($reset['password'], $this->user->password));
        $response->assertJsonStructure(['access_token']);
    }

    public function test_invalid_token(): void
    {
        $reset = $this->createReset($this->user->email, 'token', now());
        $reset['token'] = 'invalid_token';

        $response = $this->postJson(self::URI, $reset);

        $response->assertUnauthorized();
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

        $response->assertSuccess();

        $this->user->refresh();

        $this->assertTrue(Hash::check($reset['password'], $this->user->password));
        $response->assertJsonStructure(['access_token']);
    }

    public function test_expired(): void
    {
        $reset = [$this->user->email, 'expired', now()->subMinutes(config('auth.passwords.users.expire'))];
        $reset = $this->createReset(...$reset);

        $response = $this->postJson(self::URI, $reset);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->postJson(self::URI);
        $response->assertError(self::HTTP_BAD_REQUEST);
    }
}
