<?php

namespace Tests\Feature\Auth;

use App\Models\Factories\UserFactory;
use Tests\TestCase;

class RefreshTest extends TestCase
{
    const URI = 'auth/refresh';

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = app(UserFactory::class)->withTokens()->create();
    }

    public function test_refresh()
    {
        $token = $this->user->tokens()->first()->token;
        $this->assertDatabaseHas('tokens', ['token' => $token]);

        $response = $this->actingAs($this->user)->postJson(self::URI);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tokens', ['token' => $token]);
        $this->assertDatabaseHas('tokens', ['token' => $response->decodeResponseJson('access_token')]);
    }
}
