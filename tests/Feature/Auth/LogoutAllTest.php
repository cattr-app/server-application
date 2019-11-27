<?php


namespace Tests\Feature\Auth;

use App\Models\Factories\UserFactory;
use Tests\TestCase;

/**
 * Class LogoutAllTest
 * @package Tests\Feature\Auth
 */
class LogoutAllTest extends TestCase
{
    const URI = 'auth/logout-all';

    private $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = app(UserFactory::class)->withTokens(4)->create();
    }

    public function test_logout_all()
    {
        $tokens = $this->user->tokens()->get()->toArray();

        $this->assertNotEmpty($tokens);

        foreach ($tokens as $token) {
            $this->assertDatabaseHas('tokens', $token);
        }

        $response = $this->actingAs($this->user)->postJson(self::URI);
        $response->assertStatus(200);

        foreach ($tokens as $token) {
            $this->assertDatabaseMissing('tokens', $token);
        }
    }

    public function test_without_auth()
    {
        $response = $this->postJson(self::URI);
        $response->assertError(401);
    }
}
