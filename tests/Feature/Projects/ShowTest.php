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

    /** @var User $admin */
    private User $admin;
    /** @var User $manager */
    private User $manager;
    /** @var User $auditor */
    private User $auditor;
    /** @var User $user */
    private User $user;

    /** @var User $projectManager */
    private User $projectManager;
    /** @var User $projectAuditor */
    private User $projectAuditor;
    /** @var User $projectUser */
    private User $projectUser;

    /** @var Project $project */
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->project = ProjectFactory::create();

        $this->projectManager = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectManager->projects()->attach($this->project->id, ['role_id' => 1]);

        $this->projectAuditor = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectAuditor->projects()->attach($this->project->id, ['role_id' => 3]);

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach($this->project->id, ['role_id' => 2]);
    }

    public function test_show_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->project->only('id'));

        $response->assertOk();
        $response->assertJson($this->project->toArray());
    }

    public function test_show_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->project->only('id'));

        $response->assertOk();
        $response->assertJson($this->project->toArray());
    }

    public function test_show_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->project->only('id'));

        $response->assertOk();
        $response->assertJson($this->project->toArray());
    }

    public function test_show_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, $this->project->only('id'));

        $response->assertForbidden();
    }

    public function test_show_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->postJson(self::URI, $this->project->only('id'));

        $response->assertOk();
        $response->assertJson($this->project->toArray());
    }

    public function test_show_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->postJson(self::URI, $this->project->only('id'));

        $response->assertOk();
        $response->assertJson($this->project->toArray());
    }

    public function test_show_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->postJson(self::URI, $this->project->only('id'));

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
