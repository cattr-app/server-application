<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class EditTest extends TestCase
{
    use WithFaker;

    private const URI = 'tasks/edit';

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

    /** @var Task $task */
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->manager = UserFactory::refresh()->asManager()->withTokens()->create();
        $this->auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->task = TaskFactory::create();

        $this->projectManager = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectManager->projects()->attach($this->task->project_id, ['role_id' => 1]);

        $this->projectAuditor = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectAuditor->projects()->attach($this->task->project_id, ['role_id' => 3]);

        $this->projectUser = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectUser->projects()->attach($this->task->project_id, ['role_id' => 2]);
    }

    public function test_edit_as_admin(): void
    {
        $this->task->description = $this->faker->text;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->task->toArray());

        $response->assertOk();
        $response->assertJson(['res' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->task->description]);
    }

    public function test_edit_as_manager(): void
    {
        $this->task->description = $this->faker->text;

        $response = $this->actingAs($this->manager)->postJson(self::URI, $this->task->toArray());

        $response->assertOk();
        $response->assertJson(['res' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->task->description]);
    }

    public function test_edit_as_auditor(): void
    {
        $this->task->description = $this->faker->text;

        $response = $this->actingAs($this->auditor)->postJson(self::URI, $this->task->toArray());

        $response->assertForbidden();
    }

    public function test_edit_as_user(): void
    {
        $this->task->description = $this->faker->text;

        $response = $this->actingAs($this->user)->postJson(self::URI, $this->task->toArray());

        $response->assertForbidden();
    }

    public function test_edit_as_project_manager(): void
    {
        $this->task->description = $this->faker->text;

        $response = $this->actingAs($this->projectManager)->postJson(self::URI, $this->task->toArray());

        $response->assertOk();
        $response->assertJson(['res' => $this->task->toArray()]);
        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'description' => $this->task->description]);
    }

    public function test_edit_as_project_auditor(): void
    {
        $this->task->description = $this->faker->text;

        $response = $this->actingAs($this->projectAuditor)->postJson(self::URI, $this->task->toArray());

        $response->assertForbidden();
    }

    public function test_edit_as_project_project_user(): void
    {
        $this->task->description = $this->faker->text;

        $response = $this->actingAs($this->projectUser)->postJson(self::URI, $this->task->toArray());

        $response->assertForbidden();
    }

    public function test_edit_not_existing(): void
    {
        $this->task->id = Task::withoutGlobalScopes()->count() + 20;

        $response = $this->actingAs($this->admin)->postJson(self::URI, $this->task->toArray());

        $response->assertValidationError();
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
