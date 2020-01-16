<?php
namespace Tests\Feature\Roles;

use App\User;

use App\Models\Role;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class CountTest
 * @package Tests\Feature\Roles
 */
class CountTest extends TestCase
{
    private const URI = 'v1/roles/count';

    /**
     * @var User
     */
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::withTokens()->asAdmin()->create();
    }

    public function test_count()
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertSuccess();
        $response->assertJson(['total' => Role::count()]);
    }

    public function test_unauthorized()
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
