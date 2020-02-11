<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class RefreshTest
 */
class RefreshTest extends TestCase
{
    private const URI = 'auth/refresh';

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::withTokens()->create();
    }

    public function test_refresh(): void
    {
        $token = $this->user->tokens()->first()->token;
        $this->assertDatabaseHas('tokens', ['token' => $token]);

        $response = $this->actingAs($this->user)->postJson(self::URI);

        $response->assertSuccess();
        $this->assertDatabaseMissing('tokens', ['token' => $token]);
        $this->assertDatabaseHas('tokens', ['token' => $response->decodeResponseJson('access_token')]);
    }
}
