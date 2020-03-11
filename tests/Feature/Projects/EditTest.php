<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use App\Models\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;


class EditTest extends TestCase
{
    private const URI = 'v1/projects/edit';

    private User $admin;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();

        $this->project = ProjectFactory::create();
    }


    public function test_edit(): void
    {
        $this->project->description = 'New Description';

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->project->toArray());

        $response->assertSuccess();
        $response->assertJson(['res' => $this->project->toArray()]);
        $this->assertDatabaseHas('projects', $this->project->toArray());
    }

    public function test_not_existing_project(): void
    {
        $this->project->id = 42;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->project->toArray());

        $response->assertNotFound();
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
