<?php

namespace Tests\Feature\Auth;

use Tests\Factories\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class LoginTest
 * @package Tests\Feature\Auth
 */
class LoginTest extends TestCase
{
    const URI = 'auth/login';

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

        $this->user = app(UserFactory::class)->create();
        $this->loginData = [
            'email' => $this->user->email,
            'password' => $this->user->full_name
        ];
    }

    public function test_success()
    {
        $response = $this->postJson(self::URI, $this->loginData);
        $response->assertApiSuccess();

        $token = $this->user->tokens()->first()->token;
        $response->assertJson(['access_token' => $token]);
    }

    public function test_wrong_credentials()
    {
        $this->loginData['password'] = 'wrong_password';
        $response = $this->postJson(self::URI, $this->loginData);
        $response->assertApiError(401);
    }

    public function test_disabled_user()
    {
        $this->user->active = false;
        $this->user->save();
        $response = $this->postJson(self::URI, $this->loginData);
        $response->assertApiError(403);
    }

    public function test_soft_deleted_user()
    {
        $this->user->delete();
        $response = $this->postJson(self::URI, $this->loginData);
        $response->assertApiError(401);
    }

    public function test_without_params()
    {
        $response = $this->postJson(self::URI);
        $response->assertApiError(400);
    }

    // TODO Captcha Tests
}
