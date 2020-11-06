<?php

namespace Tests\Feature\Projects;

use App\Models\Project;
use App\Models\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'projects/list';

    private const PROJECTS_AMOUNT = 10;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        ProjectFactory::createMany(self::PROJECTS_AMOUNT);

        $this->projectManager = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectManager->projects()->attach(Project::first()->id, ['role_id' => 1]);

        $this->projectAuditor = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectAuditor->projects()->attach(Project::first()->id, ['role_id' => 3]);

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach(Project::first()->id, ['role_id' => 2]);
    }

    public function test_list_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(Project::all()->toArray());
    }

    public function test_list_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(Project::all()->toArray());
    }

    public function test_list_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->getJson(self::URI);

        $response->assertOk();
        $response->assertJson(Project::all()->toArray());
    }

    public function test_list_as_user(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);

        $response->assertOk();
        $response->assertExactJson([]);
    }

    public function test_list_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->getJson(self::URI);

        $response->assertOk();
        $response->assertExactJson([Project::first()->toArray()]);
    }

    public function test_list_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->getJson(self::URI);

        $response->assertOk();
        $response->assertExactJson([Project::first()->toArray()]);
    }

    public function test_list_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->getJson(self::URI);

        $response->assertOk();
        $response->assertExactJson([Project::first()->toArray()]);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
