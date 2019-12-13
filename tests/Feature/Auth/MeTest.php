<?php

namespace Tests\Feature\Auth;

use Tests\Factories\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class MeTest
 * @package Tests\Feature\Auth
 */
class MeTest extends TestCase
{
    const URI = 'auth/me';

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = app(UserFactory::class)
            ->withTokens()
            ->create();
    }

    public function test_me()
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);
        $response->assertApiSuccess();
        $response->assertJson(['user' => $this->user->toArray()]);
    }

    public function test_without_auth()
    {
        $response = $this->get(self::URI);
        $response->assertApiError(401);
    }
}
