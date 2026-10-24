<?php
namespace Tests\Feature\Roles;

use App\Enums\Role;
use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'roles/list';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::withTokens()->asAdmin()->create();
    }

    public function test_list(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(array_map(
            static fn (Role $role) => ['name' => $role->name, 'id' => $role->value],
            Role::cases()
        ));
    }

    public function test_list_is_public(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertOk();
    }
}
