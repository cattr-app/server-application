<?php

namespace Tests\Feature\Auth;

use App\Models\Factories\UserFactory;
use Tests\TestCase;
class MeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->uri = '/auth/me';
        $this->user = app(UserFactory::class)->withTokens()->create();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->user->forceDelete();
    }

    public function test_me()
    {
        $response = $this->actingAs($this->user)->get($this->uri);
        $response->assertStatus(200);
        $response->assertJson(['id' => $this->user->id]);
    }

    public function test_without_auth()
    {
        $response = $this->get($this->uri);
        $response->assertError(401);
    }
}
