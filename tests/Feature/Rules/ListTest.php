<?php

namespace Tests\Feature\Rules;

use Tests\Factories\UserFactory;
use App\Models\Rule;
use App\User;
use Tests\TestCase;

/**
 * Class ListTest
 * @package Tests\Feature\Rules
 */
class ListTest extends TestCase
{
    const URI = 'v1/rules/list';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = app(UserFactory::class)
            ->withTokens()
            ->asUser()
            ->create();

        $this->admin = app(UserFactory::class)
            ->withTokens()
            ->asAdmin()
            ->create();
    }

    public function test_list()
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);
        $response->assertOK();
        $response->assertJson(Rule::all()->toArray());
    }

    public function test_unauthorized()
    {
        $response = $this->getJson(self::URI);
        $response->assertApiError(401);
    }

    public function test_forbidden()
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);
        $response->assertApiError(403, True);
    }
}
