<?php

namespace Tests\Feature\ProjectsUsers;

use App\Models\ProjectsUsers;
use App\Models\User;
use Tests\Facades\ProjectUserFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class RemoveTest
 */
class RemoveTest extends TestCase
{
    private const URI = 'v1/projects-users/remove';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var ProjectsUsers
     */
    private $projectUser;

    /**
     * @var array
     */
    private $requestData;


    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->projectUser = ProjectUserFactory::create();

        $this->requestData = $this->projectUser->only(['project_id', 'user_id']);
    }

    public function test_remove(): void
    {
        $this->assertDatabaseHas('projects_users', $this->requestData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->requestData);

        $response->assertSuccess();
        $this->assertDatabaseMissing('projects_users', $this->requestData);
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
