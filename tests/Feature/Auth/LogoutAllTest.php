<?php


namespace Tests\Feature\Auth;

use App\Models\Factories\UserFactory;
use Tests\TestCase;

class LogoutFromAllTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->uri = 'auth/logout-all';
        $this->user = app(UserFactory::class)->withTokens(4)->create();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->user->forceDelete();
    }

    public function test_logout_from_all()
    {
        $tokens = $this->user->tokens()->get()->toArray();

        $this->assertNotEmpty($tokens);

        foreach ($tokens as $token) {
            $this->assertDatabaseHas('tokens', $token);
        }

        $response = $this->actingAs($this->user)->postJson($this->uri);
        $response->assertStatus(200);

        foreach ($tokens as $token) {
            $this->assertDatabaseMissing('tokens', $token);
        }
    }

    public function test_without_auth()
    {
        $response = $this->postJson($this->uri);
        $response->assertError(401);
    }
}
