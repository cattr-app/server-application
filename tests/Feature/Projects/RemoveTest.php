<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use Tests\Factories\ProjectFactory;
use Tests\Factories\UserFactory;
use App\User;
use Tests\TestCase;

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

        $this->admin = app(UserFactory::class)
            ->withTokens()
            ->asAdmin()
            ->create();

        $this->project = app(ProjectFactory::class)->create();
    }

    public function test_remove()
    {
        $this->assertDatabaseHas('projects', $this->project->toArray());

        $response = $this->actingAs($this->admin)->postJson(self::URI, ['id' => $this->project->id]);

        $response->assertApiSuccess();
        $this->assertSoftDeleted('projects', ['id' => $this->project->id]);

    }

    public function test_unauthorized()
    {
        $response = $this->postJson(self::URI);

        $response->assertApiError(401);
    }

    public function test_without_params()
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI);

        $response->assertApiError(400, true);
    }
}
