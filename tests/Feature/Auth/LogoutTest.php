<?php

namespace Tests\Feature\Auth;

use App\User;
use Tests\TestCase;
use App\Models\Factories\UserFactory;

/**
 * Class LogoutTest
 * @package Tests\Feature\Auth
 */
class LogoutTest extends TestCase
{
    const URI = 'auth/logout';

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = app(UserFactory::class)
            ->withTokens()
            ->create();
    }

    public function test_logout()
    {
        $token = $this->user->tokens()->first()->token;
        $this->assertDatabaseHas('tokens', ['token' => $token]);

        $response = $this->actingAs($this->user)->postJson(self::URI);

        $response->assertApiSuccess();
        $this->assertDatabaseMissing('tokens', ['token' => $token]);
    }

    public function test_unauthorized()
    {
        $response = $this->postJson(self::URI);
        $response->assertApiError(401);
    }
}
