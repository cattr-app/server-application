<?php

namespace Tests\Feature\Auth;

use App\Models\Factories\UserFactory;
use Tests\TestCase;



class LoginTest extends TestCase
{
    /**
     * @var array $loginData
     */
    private $loginData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uri = 'auth/login';

        $this->user = app(UserFactory::class)->create();
        $this->loginData = [
            'login' => $this->user->email,
            'password' => $this->user->full_name
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->user->forceDelete();
    }

    public function test_success()
    {
        $response = $this->postJson($this->uri, $this->loginData);
        $response->assertStatus(200);

        $token = $this->user->tokens()->first()->token;
        $response->assertJson(['access_token' => $token]);
        // TODO Check Structure
    }

    public function test_wrong_credentials()
    {
        $this->loginData['password'] = 'wrong_password';
        $response = $this->postJson($this->uri, $this->loginData);
        $response->assertError(401);
    }

    public function test_disabled_user()
    {
        $this->user->active = false;
        $this->user->save();
        $response = $this->postJson($this->uri, $this->loginData);
        $response->assertError(403);
    }

    public function test_soft_deleted_user()
    {
        $this->user->delete();
        $response = $this->postJson($this->uri, $this->loginData);
        $response->assertError(401);
    }

    // TODO Captcha Tests
}
