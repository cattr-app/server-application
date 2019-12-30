<?php


namespace Tests\Feature\Screenshot;


use App\Models\Screenshot;
use App\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'v1/projects/list';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var User
     */
    private $commonUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->commonUser = UserFactory::withTokens()->asUser()->create();
    }

    public function test_list()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertOk();
        $response->assertJson(Screenshot::all()->toArray());
    }

    public function test_unauthorized()
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }

    public function test_common_user()
    {
        $response = $this->actingAs($this->commonUser)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson([]);
    }
}
