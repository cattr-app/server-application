<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use App\Models\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * Class RemoveTest
 */
class RemoveTest extends TestCase
{
    private const URI = 'v1/projects/remove';

    /**
     * @var User
     */
    private $admin;

    /**
     * @var Project
     */
    private $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->project = ProjectFactory::create();
    }

    public function test_remove(): void
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->project->only('id'));

        $response->assertSuccess();
        $this->assertSoftDeleted('projects', $this->project->only('id'));

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
