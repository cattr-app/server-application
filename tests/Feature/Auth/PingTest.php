<?php

namespace Test\Feature\Auth;

use App\Models\Factories\UserFactory;
use Tests\TestCase;

class PingTest extends TestCase
{
    const URI = 'auth/logout';

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = app(UserFactory::class)->withTokens()->create();
    }

    public function test_ping()
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);
        $response->assertStatus(200);
    }

    public function test_without_auth()
    {
        $response = $this->getJson(self::URI);
        $response->assertError(401);

    }
}
