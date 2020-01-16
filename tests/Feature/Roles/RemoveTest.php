<?php

namespace Tests\Feature\Roles;

use App\Models\Project;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class RemoveTest
 * @package Tests\Feature\Roles
 */
class RemoveTest extends TestCase
{
    private const URI = 'v1/roles/remove';

    /**
     * @var User
     */
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
    }

    public function test_remove()
    {
        $this->assertDatabaseHas('role', ['id' => 1]);

        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => 1]);

        $response->assertSuccess();
        $this->assertSoftDeleted('role', ['id' => 1]);

    }

    public function test_unauthorized()
    {
        $response = $this->postJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_without_params()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertValidationError();
    }
}
