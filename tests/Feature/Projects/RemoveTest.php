<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use App\User;
use Tests\TestCase;

/**
 * Class RemoveTest
 * @package Tests\Feature\Projects
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

    public function test_remove()
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => $this->project->id]);

        $response->assertSuccess();
        $this->assertSoftDeleted('projects', $this->project->only('id'));

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
