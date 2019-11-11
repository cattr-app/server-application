<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\Factories\UserFactory;

class LogoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->uri = '/auth/logout';
        $this->user = app(UserFactory::class)->withTokens()->create();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->user->forceDelete();
    }

    public function test_logout()
    {
        $token = $this->user->tokens()->first()->token;
        $this->assertDatabaseHas('tokens', ['token' => $token]);

        $response = $this->actingAs($this->user)->post($this->uri);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tokens', ['token' => $token]);
    }
}
