<?php

namespace Tests\Feature\Roles;

use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    private const URI = 'roles/remove';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
    }

    public function test_remove(): void
    {
        $this->assertDatabaseHas('role', ['id' => 1]);

        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => 1]);

        $response->assertSuccess();
        $this->assertSoftDeleted('role', ['id' => 1]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
