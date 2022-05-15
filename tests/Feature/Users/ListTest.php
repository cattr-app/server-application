<?php

namespace Tests\Feature\Users;

use App\Models\Project;
use App\Models\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'users/list';

    private const USERS_AMOUNT = 10;

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

        UserFactory::createMany(self::USERS_AMOUNT);

        $this->project = ProjectFactory::create();

        $this->projectManager = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectManager->projects()->attach($this->project->id, ['role_id' => 1]);

        $this->projectAuditor = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectAuditor->projects()->attach($this->project->id, ['role_id' => 3]);

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach($this->project->id, ['role_id' => 2]);
    }

    public function test_list_as_admin(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $users = User::withoutGlobalScopes()->setEagerLoads([])->get()->toArray();

        $response->assertOk();
        $response->assertExactJson($users);
    }

    public function test_list_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->getJson(self::URI);

        $users = User::withoutGlobalScopes()->setEagerLoads([])->get()->toArray();

        $response->assertOk();
        $response->assertExactJson($users);
    }

    public function test_list_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->getJson(self::URI);

        $users = User::withoutGlobalScopes()->setEagerLoads([])->get()->toArray();

        $response->assertOk();
        $response->assertExactJson($users);
    }

    public function test_list_as_user(): void
    {
        $response = $this->actingAs($this->user)->getJson(self::URI);

        $user = User::withoutGlobalScopes()
            ->where('id', $this->user->id)
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $response->assertExactJson($user);
    }

    public function test_list_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->getJson(self::URI);

        $users = User::withoutGlobalScopes()
            ->whereHas('projects', function ($query) {
                $query->where('project_id', $this->project->id);
            })
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $response->assertExactJson($users);
    }

    public function test_list_as_project_manager_with_global_scope(): void
    {
        $response = $this->actingAs($this->projectManager)->postJson(self::URI, ['global_scope' => true]);

        $users = User::withoutGlobalScope(\App\Scopes\UserAccessScope::class)
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $response->assertExactJson($users);
    }

    public function test_list_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->getJson(self::URI);

        $users = User::withoutGlobalScopes()
            ->whereHas('projects', function ($query) {
                $query->where('project_id', $this->project->id);
            })
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $response->assertExactJson($users);
    }

    public function test_list_as_project_user(): void
    {
        $response = $this->actingAs($this->projectManager)->getJson(self::URI);

        $users = User::withoutGlobalScopes()
            ->whereHas('projects', function ($query) {
                $query->where('project_id', $this->project->id);
            })
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $response->assertExactJson($users);
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
