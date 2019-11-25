<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\Factories\UserFactory;

class LogoutTest extends TestCase
{
    const URI = 'auth/logout';

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

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tokens', ['token' => $token]);
    }
}
