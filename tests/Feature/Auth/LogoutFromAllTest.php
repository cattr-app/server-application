<?php


namespace Tests\Feature\Auth;

use App\Models\Factories\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class LogoutFromAllTest
 * @package Tests\Feature\Auth
 */
class LogoutFromAllTest extends TestCase
{
    const URI = 'auth/logout-from-all';

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = app(UserFactory::class)
            ->withTokens(4)
            ->create();
    }

    public function test_logout_from_all()
    {
        $tokens = $this->user->tokens()->get()->toArray();

        $this->assertNotEmpty($tokens);

        foreach ($tokens as $token) {
            $this->assertDatabaseHas('tokens', $token);
        }

        $response = $this->actingAs($this->user)->postJson(self::URI);
        $response->assertApiSuccess();

        foreach ($tokens as $token) {
            $this->assertDatabaseMissing('tokens', $token);
        }
    }

    public function test_unauthorized()
    {
        $response = $this->postJson(self::URI);
        $response->assertApiError(401);
    }
}
