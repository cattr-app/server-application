<?php

namespace Tests\Feature\Rules;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class EditTest extends TestCase
{
    private const URI = 'v1/rules/edit';

    private User $admin;
    private User $user;

    private array $correctRule;
    private array $incorrectRule;

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

    public function test_edit(): void
    {
        $this->assertDatabaseMissing('rule', $this->correctRule);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->correctRule);

        $this->assertDatabaseHas('rule', $this->correctRule);
        $response->assertSuccess();
    }

    public function test_not_existing_rule(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->incorrectRule);

        $response->assertNotFound();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_forbidden(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI);

        $response->assertForbidden();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
