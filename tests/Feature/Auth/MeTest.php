<?php

namespace Tests\Feature\Auth;

use App\Models\Factories\UserFactory;
use Tests\TestCase;
class MeTest extends TestCase
{
    const URI = 'auth/me';

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = app(UserFactory::class)->withTokens()->create();
    }

    public function test_me()
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);
        $response->assertStatus(200);
        $response->assertJson(['id' => $this->user->id]);
    }

    public function test_without_auth()
    {
        $response = $this->get(self::URI);
        $response->assertError(401);
    }
}
