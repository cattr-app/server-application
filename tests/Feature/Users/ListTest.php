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
        $response = $this->actingAs($this->admin)->withHeader('X-Paginate', 'false')->getJson(self::URI);

        $users = User::withoutGlobalScopes()->setEagerLoads([])->get()->toArray();

        $response->assertOk();
        $this->assertEqualsCanonicalizing(collect($users)->pluck('id')->all(), collect($response->json('data'))->pluck('id')->all());
    }

    public function test_list_as_manager(): void
    {
        $response = $this->actingAs($this->manager)->withHeader('X-Paginate', 'false')->getJson(self::URI);

        $users = User::withoutGlobalScopes()->setEagerLoads([])->get()->toArray();

        $response->assertOk();
        $this->assertEqualsCanonicalizing(collect($users)->pluck('id')->all(), collect($response->json('data'))->pluck('id')->all());
    }

    public function test_list_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->withHeader('X-Paginate', 'false')->getJson(self::URI);

        $users = User::withoutGlobalScopes()->setEagerLoads([])->get()->toArray();

        $response->assertOk();
        $this->assertEqualsCanonicalizing(collect($users)->pluck('id')->all(), collect($response->json('data'))->pluck('id')->all());
    }

    public function test_list_as_user(): void
    {
        $response = $this->actingAs($this->user)->withHeader('X-Paginate', 'false')->getJson(self::URI);

        $user = User::withoutGlobalScopes()
            ->where('id', $this->user->id)
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $this->assertSame([$this->user->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_list_as_project_manager(): void
    {
        $response = $this->actingAs($this->projectManager)->withHeader('X-Paginate', 'false')->getJson(self::URI);

        $users = User::withoutGlobalScopes()
            ->whereHas('projects', function ($query) {
                $query->where('project_id', $this->project->id);
            })
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $this->assertEqualsCanonicalizing(collect($users)->pluck('id')->all(), collect($response->json('data'))->pluck('id')->all());
    }

    public function test_list_as_project_manager_global_scope_does_not_bypass_access(): void
    {
        $response = $this->actingAs($this->projectManager)
            ->withHeader('X-Paginate', 'false')
            ->postJson(self::URI, ['global_scope' => true]);

        $users = User::withoutGlobalScopes()
            ->whereHas('projects', function ($query) {
                $query->where('project_id', $this->project->id);
            })
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $this->assertEqualsCanonicalizing(
            collect($users)->pluck('id')->all(),
            collect($response->json('data'))->pluck('id')->all()
        );
    }

    public function test_list_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->withHeader('X-Paginate', 'false')->getJson(self::URI);

        $users = User::withoutGlobalScopes()
            ->whereHas('projects', function ($query) {
                $query->where('project_id', $this->project->id);
            })
            ->setEagerLoads([])
            ->get()
            ->toArray();

        $response->assertOk();
        $this->assertEqualsCanonicalizing(collect($users)->pluck('id')->all(), collect($response->json('data'))->pluck('id')->all());
    }

    public function test_list_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->withHeader('X-Paginate', 'false')->getJson(self::URI);

        $response->assertOk();
        $this->assertSame([$this->projectUser->id], collect($response->json('data'))->pluck('id')->all());
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI);

        $response->assertUnauthorized();
    }
}
