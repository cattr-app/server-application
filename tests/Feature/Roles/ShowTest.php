<?php
namespace Tests\Feature\Roles;

use App\Models\Role;
use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'v1/roles/show';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::withTokens()->asAdmin()->create();
    }

    public function test_show(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => 1]);

        $response->assertOk();
        $response->assertJson(Role::find(1)->toArray());
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
