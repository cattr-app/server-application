<?php

namespace Test\Feature\Auth;

use App\Models\Factories\UserFactory;
use Tests\TestCase;

class PingTest extends TestCase
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

    public function test_ping()
    {
        $response = $this->actingAs($this->user)->get($this->uri);
        $response->assertStatus(200);
    }

    public function test_without_auth()
    {
        $response = $this->get($this->uri);
        $response->assertError(401);

    }
}
