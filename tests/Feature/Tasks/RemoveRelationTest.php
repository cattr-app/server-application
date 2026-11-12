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

class RemoveRelationTest extends TestCase
{
    private const URI = 'tasks/remove-relation';

    private Project $project;
    private Task $parent;
    private Task $child;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = ProjectFactory::create();
        $this->parent = TaskFactory::forProject($this->project)->create();
        $this->child = TaskFactory::forProject($this->project)->create();

        $this->parent->children()->attach($this->child);
    }

    private function removeRelation(User $user, array $data = []): TestResponse
    {
        return $this->actingAs($user)->postJson(self::URI, $data + [
            'parent_id' => $this->parent->id,
            'child_id' => $this->child->id,
        ]);
    }

    private function projectMember(int $roleId, bool $assignedBy = false): User
    {
        $user = UserFactory::refresh()->asUser()->withTokens()->create();
        $user->projects()->attach($this->project->id, ['role_id' => $roleId]);

        if ($assignedBy) {
            $this->parent->update(['assigned_by' => $user->id]);
            $this->child->update(['assigned_by' => $user->id]);
        }

        return $user;
    }

    private function assertRelated(): void
    {
        $this->assertDatabaseHas('tasks_relations', ['parent_id' => $this->parent->id, 'child_id' => $this->child->id]);
    }

    private function assertNotRelated(): void
    {
        $this->assertDatabaseMissing('tasks_relations', ['parent_id' => $this->parent->id, 'child_id' => $this->child->id]);
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
    public function test_remove_relation_as_global_role(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->removeRelation($user);

        $response->assertNoContent();
        $this->assertNotRelated();
    }

    #[DataProvider('forbiddenGlobalRoles')]
    public function test_remove_relation_is_forbidden_for_global_role(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->removeRelation($user);

        $response->assertForbidden();
        $this->assertRelated();
    }

    public function test_project_manager_can_remove_relation(): void
    {
        $response = $this->removeRelation($this->projectMember(1));

        $response->assertNoContent();
        $this->assertNotRelated();
    }

    public function test_project_user_can_remove_relation_between_tasks_they_assigned(): void
    {
        $response = $this->removeRelation($this->projectMember(2, assignedBy: true));

        $response->assertNoContent();
        $this->assertNotRelated();
    }

    public function test_project_user_cannot_remove_relation_between_tasks_assigned_by_others(): void
    {
        $response = $this->removeRelation($this->projectMember(2));

        $response->assertForbidden();
        $this->assertRelated();
    }

    public function test_project_auditor_cannot_remove_relation(): void
    {
        $response = $this->removeRelation($this->projectMember(3));

        $response->assertForbidden();
        $this->assertRelated();
    }

    public function test_user_outside_the_project_cannot_remove_relation(): void
    {
        $outsider = UserFactory::refresh()->asUser()->withTokens()->create();

        $response = $this->removeRelation($outsider);

        $response->assertForbidden();
        $this->assertRelated();
    }

    public function test_manager_cannot_remove_relation_in_external_project(): void
    {
        $this->project->update(['source' => 'gitlab']);
        $manager = UserFactory::refresh()->asManager()->withTokens()->create();

        $response = $this->removeRelation($manager);

        $response->assertForbidden();
        $this->assertRelated();
    }

    public function test_only_the_given_relation_is_removed(): void
    {
        $other = TaskFactory::forProject($this->project)->create();
        $this->parent->children()->attach($other);
        $otherParent = TaskFactory::forProject($this->project)->create();
        $otherParent->children()->attach($this->child);
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $this->removeRelation($admin)->assertNoContent();

        // The controller detaches child_id from parent->children(), so both ids are part of the deletion
        $this->assertNotRelated();
        $this->assertDatabaseHas('tasks_relations', ['parent_id' => $this->parent->id, 'child_id' => $other->id]);
        $this->assertDatabaseHas('tasks_relations', ['parent_id' => $otherParent->id, 'child_id' => $this->child->id]);
    }

    public function test_relation_is_directional(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->removeRelation($admin, ['parent_id' => $this->child->id, 'child_id' => $this->parent->id]);

        $response->assertNoContent();
        $this->assertRelated();
    }

    public function test_removing_a_missing_relation_is_a_no_op(): void
    {
        $unrelated = TaskFactory::forProject($this->project)->create();
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->removeRelation($admin, ['child_id' => $unrelated->id]);

        $response->assertNoContent();
        $this->assertRelated();
    }

    public static function invalidData(): array
    {
        return [
            'missing parent_id' => [['parent_id' => null]],
            'missing child_id' => [['child_id' => null]],
            'non-integer parent_id' => [['parent_id' => 'abc']],
            'non-integer child_id' => [['child_id' => 'abc']],
            'non-existent parent_id' => [['parent_id' => 999999]],
            'non-existent child_id' => [['child_id' => 999999]],
        ];
    }

    #[DataProvider('invalidData')]
    public function test_validation(array $data): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->removeRelation($admin, $data);

        $response->assertValidationError();
        $this->assertRelated();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, [
            'parent_id' => $this->parent->id,
            'child_id' => $this->child->id,
        ]);

        $response->assertUnauthorized();
        $this->assertRelated();
    }
}
