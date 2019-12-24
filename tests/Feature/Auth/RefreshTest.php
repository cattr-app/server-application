<?php

namespace Tests\Feature\Auth;

use Tests\Facades\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class RefreshTest
 * @package Tests\Feature\Auth
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

    public function test_refresh()
    {
        $token = $this->user->tokens()->first()->token;
        $this->assertDatabaseHas('tokens', ['token' => $token]);

        $response = $this->actingAs($this->user)->postJson(self::URI);

        $response->assertApiSuccess();
        $this->assertDatabaseMissing('tokens', ['token' => $token]);
        $this->assertDatabaseHas('tokens', ['token' => $response->decodeResponseJson('access_token')]);
    }
}
