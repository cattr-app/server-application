<?php

namespace Tests\Feature\Auth;

use Tests\Facades\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class MeTest
 */
class MeTest extends TestCase
{
    private const URI = 'auth/me';

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::withTokens()->create();
    }

    public function test_me(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);

        $response->assertSuccess();
        $response->assertJson(['user' => $this->user->toArray()]);
    }

    public function test_without_auth(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
