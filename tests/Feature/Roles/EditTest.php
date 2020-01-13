<?php

namespace Tests\Feature\Roles;

use Tests\Facades\UserFactory;
use App\User;
use Tests\TestCase;


/**
 * Class EditTest
 * @package Tests\Feature\Roles
 */
class EditTest extends TestCase
{
    private const URI = 'v1/roles/edit';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var string
     */
    private $newRoleData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->newRoleData = ['id' => 1, 'name' => 'new-name'];
    }

    public function test_edit()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->newRoleData);

        $response->assertSuccess();
        $this->assertDatabaseHas('role', $this->newRoleData);
    }

    public function test_not_existing_role()
    {
        $this->newRoleData['id'] = 42;
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->newRoleData);

        $response->assertItemNotFound();
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
