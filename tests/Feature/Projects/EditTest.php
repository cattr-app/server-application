<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use Tests\Factories\ProjectFactory;
use Tests\Factories\UserFactory;
use App\User;
use Tests\TestCase;


class EditTest extends TestCase
{
    private const URI = 'v1/projects/edit';

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


    public function test_edit()
    {
        $this->project->description = 'New Description';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->project->toArray());

        $response->assertApiSuccess();
        $this->assertDatabaseHas('projects', $this->project->toArray());

    }

    public function test_not_existing_project()
    {
        $this->project->id = 42;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->project->toArray());

        $response->assertApiError(404);
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
