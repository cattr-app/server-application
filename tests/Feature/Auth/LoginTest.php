<?php

namespace Tests\Feature\Auth;

use App\Models\Factories\UserFactory;
use Tests\TestCase;

/**
 * Class LoginTest
 * @package Tests\Feature\Auth
 */
class LoginTest extends TestCase
{
    const URI = 'auth/login';

    private $user;

    private $loginData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::create();
        $this->loginData = [
            'login' => $this->user->email,
            'password' => $this->user->full_name
        ];
    }


    public function test_success()
    {
        $response = $this->postJson(self::URI, $this->loginData);
        $response->assertStatus(200);

        $token = $this->user->tokens()->first()->token;
        $response->assertJson(['access_token' => $token]);
    }

    public function test_wrong_credentials()
    {
        $this->loginData['password'] = 'wrong_password';
        $response = $this->postJson(self::URI, $this->loginData);
        $response->assertError(401);
    }

    public function test_disabled_user()
    {
        $this->user->active = false;
        $this->user->save();
        $response = $this->postJson(self::URI, $this->loginData);
        $response->assertError(403);
    }

    public function test_soft_deleted_user()
    {
        $this->user->delete();
        $response = $this->postJson(self::URI, $this->loginData);
        $response->assertError(401);
    }

    // TODO Captcha Tests
}
