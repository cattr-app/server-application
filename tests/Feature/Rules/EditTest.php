<?php

namespace Tests\Feature\Rules;

use Tests\Factories\Facades\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class EditTest
 * @package Tests\Feature\Rules
 */
class EditTest extends TestCase
{
    private const URI = 'v1/rules/edit';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var User
     */
    private $user;

    /**
     * @var array
     */
    private $correctRule;

    /**
     * @var array
     */
    private $incorrectRule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::asUser()->withTokens()->create();
        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->correctRule = [
            'role_id' => 1,
            'object' => 'register',
            'action' => 'create',
            'allow' => 0
        ];

        $this->incorrectRule = [
            'role_id' => 1,
            'object' => 'unknown',
            'action' => 'create',
            'allow' => 1
        ];
    }

    public function test_edit()
    {
        $this->assertDatabaseMissing('rule', $this->correctRule);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->correctRule);

        $this->assertDatabaseHas('rule', $this->correctRule);
        $response->assertApiSuccess();
    }

    public function test_not_existing_rule()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->incorrectRule);
        $response->assertApiError(404);
    }

    public function test_unauthorized()
    {
        $response = $this->postJson(self::URI);
        $response->assertApiError(401);
    }

    public function test_forbidden()
    {
        $response = $this->actingAs($this->user)->postJson(self::URI);
        $response->assertApiError(403, true);
    }

    public function test_without_params()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);
        $response->assertApiError(400, true);
    }
}
