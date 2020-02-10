<?php

namespace Tests\Feature\ProjectsUsers;

use App\Models\User;
use Tests\Facades\ProjectUserFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class CreateTest
 */
class CreateTest extends TestCase
{
    private const URI = 'v1/projects-users/create';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var array
     */
    private $projectUserData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->projectUserData = ProjectUserFactory::generateProjectUserData();
    }

    public function test_create(): void
    {
        $this->assertDatabaseMissing('projects_users', $this->projectUserData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->projectUserData);

        $response->assertOk();
        $this->assertDatabaseHas('projects_users', $this->projectUserData);
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
