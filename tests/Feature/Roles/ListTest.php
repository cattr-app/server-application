<?php
namespace Tests\Feature\Roles;

use App\User;

use App\Models\Role;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class ListTest
 */
class ListTest extends TestCase
{
    private const URI = 'v1/roles/list';

    /**
     * @var User
     */
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::withTokens()->asAdmin()->create();
        $this->commonUser = UserFactory::withTokens()->asUser()->create();
    }

    public function test_list(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(Role::all()->toArray());
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
