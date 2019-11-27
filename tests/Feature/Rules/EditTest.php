<?php

namespace Tests\Feature\Rules;

use App\Models\Factories\UserFactory;
use App\Models\Rule;
use Tests\TestCase;

/**
 * Class EditTest
 * @package Tests\Feature\Rules
 */
class EditTest extends TestCase
{
    const URI = 'v1/rules/edit';

    private $admin;

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

    public function test_edit()
    {
        $data = [
            'role_id' => 1,
            'object' => 'register',
            'action' => 'create',
            'allow' => 0
        ];

        $this->assertDatabaseMissing('rule', $data);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $data);

        $this->assertDatabaseHas('rule', $data);
        $response->assertStatus(200);
    }

    public function test_not_existing_rule()
    {
        $data = [
            'role_id' => 1,
            'object' => 'unknown',
            'action' => 'create',
            'allow' => 1
        ];

        $response = $this->actingAs($this->admin)->postJson(self::URI, $data);

        //TODO fix response format to asserting with structure check
        $response->assertStatus(400);
    }

    public function test_unauthorized()
    {
        $response = $this->postJson(self::URI);
        $response->assertError(401);
    }

    public function test_forbidden()
    {
        $response = $this->actingAs($this->user)->postJson(self::URI);

        //TODO fix response format to asserting with structure check
        $response->assertStatus(403);
    }
}
