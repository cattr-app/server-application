<?php

namespace Tests\Feature\ProjectsUsers;

use App\Models\ProjectsUsers;
use Tests\Facades\ProjectUserFactory;
use Tests\Facades\UserFactory;
use App\Models\User;
use Tests\TestCase;

/**
 * Class CountTest
 */
class CountTest extends TestCase
{
    private const URI = 'v1/projects-users/count';

    private const PROJECTS_USERS_AMOUNT = 10;

    /**
     * @var User
     */
    private $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        ProjectUserFactory::createMany(self::PROJECTS_USERS_AMOUNT);
    }

    public function test_count(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertSuccess();
        $response->assertJson(['total' => ProjectsUsers::count()]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
