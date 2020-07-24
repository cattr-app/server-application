<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use App\Models\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'projects/show';

    private User $admin;
    private User $assignedUser;
    private User $notAssignedUser;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->assignedUser = UserFactory::withTokens()->asUser()->create();
        $this->notAssignedUser = UserFactory::withTokens()->asUser()->create();

        $this->project = ProjectFactory::forUsers([$this->assignedUser])->create();
    }

    public function test_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->project->only('id'));

        $response->assertOk();
        $response->assertJson($this->project->toArray());
    }

    public function test_assigned(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->project->only('id'));

        $response->assertOk();
        $response->assertJson($this->project->toArray());
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
