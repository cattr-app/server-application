<?php

namespace Tests\Feature\Auth;

use Tests\Facades\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class LoginTest
 */
class LoginTest extends TestCase
{
    private const URI = 'auth/login';

    /**
     * @var User
     */
    private $user;

    /**
     * @var array
     */
    private $loginData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::create();

        $this->loginData = [
            'email' => $this->user->email,
            'password' => $this->user->full_name
        ];
    }

    public function test_success(): void
    {
        $response = $this->postJson(self::URI, $this->loginData);
        $response->assertSuccess();

        $token = $this->user->tokens()->first()->token;

        $response->assertJson(['access_token' => $token]);
    }

    public function test_wrong_credentials(): void
    {
        $this->loginData['password'] = 'wrong_password';
        $response = $this->postJson(self::URI, $this->loginData);

        $response->assertUnauthorized();
    }

    public function test_disabled_user(): void
    {
        $this->user->active = false;
        $this->user->save();
        $response = $this->postJson(self::URI, $this->loginData);

        $response->assertForbidden('authorization.user_disabled', false);
    }

    public function test_soft_deleted_user(): void
    {
        $this->user->delete();
        $response = $this->postJson(self::URI, $this->loginData);

        $response->assertUnauthorized();
    }

    public function test_without_params()
    {
        $response = $this->postJson(self::URI);

        $response->assertError(400);
    }
}
