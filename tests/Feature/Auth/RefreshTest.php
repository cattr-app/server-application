<?php

namespace Tests\Feature\Auth;

use App\Models\Factories\UserFactory;
use Tests\TestCase;

class RefreshTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->uri = '/auth/refresh';
        $this->user = app(UserFactory::class)->withToken()->create();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->user->forceDelete();
    }

    public function test_refresh()
    {
        $token = $this->user->tokens()->first()->token;
        $this->assertDatabaseHas('tokens', ['token' => $token]);

        $response = $this->actingAs($this->user)->post($this->uri);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tokens', ['token' => $token]);
        $this->assertDatabaseHas('tokens', ['token' => $response->decodeResponseJson('access_token')]);
    }
}
