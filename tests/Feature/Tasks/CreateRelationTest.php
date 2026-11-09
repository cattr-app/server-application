<?php

namespace Tests\Feature\Tasks;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;
use Tests\TestResponse;

class CreateRelationTest extends TestCase
{
    private const URI = 'tasks/create-relation';

    private Project $project;
    private Task $task;
    private Task $related;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = ProjectFactory::create();
        $this->task = TaskFactory::forProject($this->project)->create();
        $this->related = TaskFactory::forProject($this->project)->create();
    }

    private function createRelation(User $user, array $data = []): TestResponse
    {
        return $this->actingAs($user)->postJson(self::URI, $data + [
            'task_id' => $this->task->id,
            'related_task_id' => $this->related->id,
            'relation_type' => 'follows',
        ]);
    }

    private function admin(): User
    {
        return UserFactory::refresh()->asAdmin()->withTokens()->create();
    }

    private function projectMember(int $roleId, bool $assignedBy = false): User
    {
        $user = UserFactory::refresh()->asUser()->withTokens()->create();
        $user->projects()->attach($this->project->id, ['role_id' => $roleId]);

        if ($assignedBy) {
            $this->task->update(['assigned_by' => $user->id]);
            $this->related->update(['assigned_by' => $user->id]);
        }

        return $user;
    }

    private function assertRelated(Task $parent, Task $child): void
    {
        $this->assertDatabaseHas('tasks_relations', ['parent_id' => $parent->id, 'child_id' => $child->id]);
    }

    private function assertNotRelated(Task $parent, Task $child): void
    {
        $this->assertDatabaseMissing('tasks_relations', ['parent_id' => $parent->id, 'child_id' => $child->id]);
    }

    public static function allowedGlobalRoles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
        ];
    }

    public static function forbiddenGlobalRoles(): array
    {
        return [
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    #[DataProvider('allowedGlobalRoles')]
    public function test_create_relation_as_global_role(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->createRelation($user);

        $response->assertOk();
        $this->assertRelated($this->related, $this->task);
    }

    #[DataProvider('forbiddenGlobalRoles')]
    public function test_create_relation_is_forbidden_for_global_role(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->createRelation($user);

        $response->assertForbidden();
        $this->assertNotRelated($this->related, $this->task);
    }

    public function test_project_manager_can_create_relation(): void
    {
        $response = $this->createRelation($this->projectMember(1));

        $response->assertOk();
        $this->assertRelated($this->related, $this->task);
    }

    public function test_project_user_can_create_relation_between_tasks_they_assigned(): void
    {
        $response = $this->createRelation($this->projectMember(2, assignedBy: true));

        $response->assertOk();
        $this->assertRelated($this->related, $this->task);
    }

    public function test_project_user_cannot_create_relation_between_tasks_assigned_by_others(): void
    {
        $response = $this->createRelation($this->projectMember(2));

        $response->assertForbidden();
        $this->assertNotRelated($this->related, $this->task);
    }

    public function test_project_user_needs_to_have_assigned_both_tasks(): void
    {
        $user = $this->projectMember(2);
        $this->task->update(['assigned_by' => $user->id]);

        $response = $this->createRelation($user);

        $response->assertForbidden();
        $this->assertNotRelated($this->related, $this->task);
    }

    public function test_project_auditor_cannot_create_relation(): void
    {
        $response = $this->createRelation($this->projectMember(3));

        $response->assertForbidden();
        $this->assertNotRelated($this->related, $this->task);
    }

    public function test_user_outside_the_project_cannot_create_relation(): void
    {
        $outsider = UserFactory::refresh()->asUser()->withTokens()->create();

        $response = $this->createRelation($outsider);

        $response->assertForbidden();
        $this->assertNotRelated($this->related, $this->task);
    }

    public function test_manager_cannot_create_relation_in_external_project(): void
    {
        $this->project->update(['source' => 'gitlab']);
        $manager = UserFactory::refresh()->asManager()->withTokens()->create();

        $response = $this->createRelation($manager);

        $response->assertForbidden();
        $this->assertNotRelated($this->related, $this->task);
    }

    public function test_follows_makes_the_related_task_a_parent(): void
    {
        $response = $this->createRelation($this->admin(), ['relation_type' => 'follows']);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['id', 'task_name', 'project_id', 'pivot' => ['child_id', 'parent_id']]])
            ->assertJsonPath('data.id', $this->related->id)
            ->assertJsonPath('data.pivot.parent_id', $this->related->id)
            ->assertJsonPath('data.pivot.child_id', $this->task->id);
        $this->assertRelated($this->related, $this->task);
        $this->assertNotRelated($this->task, $this->related);
    }

    public function test_precedes_makes_the_related_task_a_child(): void
    {
        $response = $this->createRelation($this->admin(), ['relation_type' => 'precedes']);

        $response->assertOk()
            ->assertJsonPath('data.id', $this->related->id)
            ->assertJsonPath('data.pivot.parent_id', $this->task->id)
            ->assertJsonPath('data.pivot.child_id', $this->related->id);
        $this->assertRelated($this->task, $this->related);
        $this->assertNotRelated($this->related, $this->task);
    }

    public function test_child_without_dates_inherits_the_date_of_the_parent(): void
    {
        $this->related->update(['start_date' => '2026-03-02', 'due_date' => '2026-03-06']);

        $this->createRelation($this->admin(), ['relation_type' => 'follows'])->assertOk();

        $this->assertDatabaseHas('tasks', [
            'id' => $this->task->id,
            'start_date' => '2026-03-06',
            'due_date' => '2026-03-06',
        ]);
    }

    public function test_duplicate_relation_is_a_conflict(): void
    {
        $admin = $this->admin();
        $this->createRelation($admin)->assertOk();

        $this->createRelation($admin)->assertError(409, 'task_relation.already_exists');

        $this->assertDatabaseCount('tasks_relations', 1);
    }

    #[DataProvider('relationTypes')]
    public function test_reverse_relation_is_a_conflict(string $type): void
    {
        $admin = $this->admin();
        $this->createRelation($admin, ['relation_type' => $type])->assertOk();

        $response = $this->actingAs($admin)->postJson(self::URI, [
            'task_id' => $this->related->id,
            'related_task_id' => $this->task->id,
            'relation_type' => $type,
        ]);

        $response->assertError(409, 'task_relation.already_exists');
        $this->assertDatabaseCount('tasks_relations', 1);
    }

    public static function relationTypes(): array
    {
        return [
            'follows' => ['follows'],
            'precedes' => ['precedes'],
        ];
    }

    #[DataProvider('relationTypes')]
    public function test_cyclic_relation_is_a_conflict(string $type): void
    {
        // first -> second -> third, closing the chain from either end creates a cycle
        $first = $this->task;
        $second = $this->related;
        $third = TaskFactory::forProject($this->project)->create();
        $admin = $this->admin();

        $this->createRelation($admin, ['relation_type' => 'precedes'])->assertOk();
        $this->actingAs($admin)->postJson(self::URI, [
            'task_id' => $second->id,
            'related_task_id' => $third->id,
            'relation_type' => 'precedes',
        ])->assertOk();

        $data = $type === 'precedes'
            ? ['task_id' => $third->id, 'related_task_id' => $first->id, 'relation_type' => 'precedes']
            : ['task_id' => $first->id, 'related_task_id' => $third->id, 'relation_type' => 'follows'];

        $this->actingAs($admin)->postJson(self::URI, $data)->assertError(409, 'task_relation.cyclic');

        $this->assertDatabaseCount('tasks_relations', 2);
    }

    public function test_tasks_of_different_projects_cannot_be_related(): void
    {
        $foreign = TaskFactory::refresh()->create();

        $response = $this->createRelation($this->admin(), ['related_task_id' => $foreign->id]);

        $response->assertError(409, 'task_relation.not_same_project');
        $this->assertNotRelated($foreign, $this->task);
    }

    public static function invalidData(): array
    {
        return [
            'missing task_id' => [['task_id' => null]],
            'missing related_task_id' => [['related_task_id' => null]],
            'missing relation_type' => [['relation_type' => null]],
            'non-integer task_id' => [['task_id' => 'abc']],
            'non-integer related_task_id' => [['related_task_id' => 'abc']],
            'non-existent task_id' => [['task_id' => 999999]],
            'non-existent related_task_id' => [['related_task_id' => 999999]],
            'unknown relation_type' => [['relation_type' => 'blocks']],
            'array relation_type' => [['relation_type' => ['follows']]],
        ];
    }

    #[DataProvider('invalidData')]
    public function test_validation(array $data): void
    {
        $response = $this->createRelation($this->admin(), $data);

        $response->assertValidationError();
        $this->assertDatabaseCount('tasks_relations', 0);
    }

    public function test_task_cannot_be_related_to_itself(): void
    {
        $response = $this->createRelation($this->admin(), ['related_task_id' => $this->task->id]);

        $response->assertValidationError();
        $this->assertDatabaseCount('tasks_relations', 0);
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, [
            'task_id' => $this->task->id,
            'related_task_id' => $this->related->id,
            'relation_type' => 'follows',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('tasks_relations', 0);
    }
}
