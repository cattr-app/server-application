<?php


namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class LogoutFromAllTest extends TestCase
{
    private const URI = 'auth/logout-from-all';
    private const TEST_URI = 'auth/me';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::withTokens(4)->create();
    }

    public function test_logout_from_all(): void
    {
        $tokens = cache("testing:{$this->user->id}:tokens");

        $this->assertNotEmpty($tokens);

        foreach ($tokens as $token) {
            $this->actingAs($token['token'])->get(self::TEST_URI)->assertOk();
        }

        $response = $this->actingAs($tokens[0]['token'])->postJson(self::URI);
        $response->assertOk();

        foreach ($tokens as $token) {
            $this->actingAs($token['token'])->get(self::TEST_URI)->assertUnauthorized();
        }
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }
}
