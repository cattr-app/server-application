<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class MeTest extends TestCase
{
    private const URI = 'auth/me';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::withTokens()->create();
    }

    public function test_me(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(['user' => $this->user->toArray()]);
    }

    public function test_without_auth(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
