<?php

namespace Tests\Feature\Tasks;

use App\Models\User;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class CreateTest extends TestCase
{
    private const URI = 'tasks/create';

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

    /**
     * @var array
     */
    private $taskData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->taskData = array_merge(TaskFactory::createRandomModelData(), [
            'project_id' => ProjectFactory::create()->id,
            'user_id' => UserFactory::create()->id
        ]);

        $this->projectManager = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectManager->projects()->attach($this->taskData['project_id'], ['role_id' => 1]);

        $this->projectAuditor = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectAuditor->projects()->attach($this->taskData['project_id'], ['role_id' => 3]);

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach($this->taskData['project_id'], ['role_id' => 2]);
    }

    public function test_create_as_admin(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->taskData);

        $response->assertSuccess();
        $response->assertJson(['res' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);
    }

    public function test_create_as_manager(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->taskData);

        $response->assertSuccess();
        $response->assertJson(['res' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);
    }

    public function test_create_as_auditor(): void
    {
        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->taskData);

        $response->assertForbidden();
    }

    public function test_create_as_user(): void
    {
        $response = $this->actingAs($this->user)->postJson(self::URI, $this->taskData);

        $response->assertForbidden();
    }

    public function test_create_as_project_manager(): void
    {
        $this->assertDatabaseMissing('tasks', $this->taskData);

        $response = $this->actingAs($this->projectManager)->postJson(self::URI, $this->taskData);

        $response->assertSuccess();
        $response->assertJson(['res' => $this->taskData]);
        $this->assertDatabaseHas('tasks', $this->taskData);
    }

    public function test_create_as_project_auditor(): void
    {
        $response = $this->actingAs($this->projectAuditor)->postJson(self::URI, $this->taskData);

        $response->assertForbidden();
    }

    public function test_create_as_project_user(): void
    {
        $response = $this->actingAs($this->projectUser)->postJson(self::URI, $this->taskData);

        $response->assertForbidden();
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
