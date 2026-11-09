<?php

namespace Tests\Feature\TaskComments;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class RemoveTest extends TestCase
{
    private const URI = 'task-comment/remove';

    private User $author;
    private Task $task;
    private TaskComment $comment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->task = TaskFactory::forUser($this->author)->create();
        $this->author->projects()->attach($this->task->project_id, ['role_id' => 2]);

        $this->comment = $this->commentBy($this->author);
    }

    private function commentBy(User $user): TaskComment
    {
        return TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $user->id,
            'content' => 'Comment',
        ]);
    }

    public static function privilegedRoles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
        ];
    }

    public static function unprivilegedRoles(): array
    {
        return [
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    #[DataProvider('privilegedRoles')]
    public function test_admin_and_manager_remove_foreign_comment(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->postJson(self::URI, ['id' => $this->comment->id]);

        $response->assertNoContent();
        $this->assertSoftDeleted('task_comment', ['id' => $this->comment->id]);
    }

    public function test_author_removes_own_comment(): void
    {
        $response = $this->actingAs($this->author)->postJson(self::URI, ['id' => $this->comment->id]);

        $response->assertNoContent();
        $this->assertSoftDeleted('task_comment', ['id' => $this->comment->id]);
    }

    public function test_auditor_removes_own_comment(): void
    {
        $auditor = UserFactory::refresh()->asAuditor()->withTokens()->create();
        $comment = $this->commentBy($auditor);

        $response = $this->actingAs($auditor)->postJson(self::URI, ['id' => $comment->id]);

        $response->assertNoContent();
        $this->assertSoftDeleted('task_comment', ['id' => $comment->id]);
        $this->assertDatabaseHas('task_comment', ['id' => $this->comment->id, 'deleted_at' => null]);
    }

    #[DataProvider('unprivilegedRoles')]
    public function test_foreign_comment_cannot_be_removed(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();
        $user->projects()->attach($this->task->project_id, ['role_id' => $role === 'asUser' ? 2 : 3]);

        $response = $this->actingAs($user)->postJson(self::URI, ['id' => $this->comment->id]);

        $response->assertForbidden();
        $this->assertDatabaseHas('task_comment', ['id' => $this->comment->id, 'deleted_at' => null]);
    }

    public function test_author_cannot_remove_comment_on_task_that_is_no_longer_visible(): void
    {
        $this->author->projects()->detach($this->task->project_id);
        $this->task->users()->detach($this->author->id);

        $response = $this->actingAs($this->author)->postJson(self::URI, ['id' => $this->comment->id]);

        $response->assertForbidden();
        $this->assertDatabaseHas('task_comment', ['id' => $this->comment->id, 'deleted_at' => null]);
    }

    public static function invalidIds(): array
    {
        return [
            'missing id' => [[]],
            'null id' => [['id' => null]],
            'string id' => [['id' => 'abc']],
            'numeric string id' => [['id' => '1']],
            'array id' => [['id' => [1]]],
        ];
    }

    #[DataProvider('invalidIds')]
    public function test_invalid_id(array $data): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, $data);

        $response->assertValidationError();
        $this->assertDatabaseHas('task_comment', ['id' => $this->comment->id, 'deleted_at' => null]);
    }

    public function test_unknown_comment_is_not_found(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => 999999]);

        $response->assertNotFound('page_not_found');
    }

    public function test_already_removed_comment_is_not_found(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $this->comment->delete();

        $response = $this->actingAs($admin)->postJson(self::URI, ['id' => $this->comment->id]);

        $response->assertNotFound('page_not_found');
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, ['id' => $this->comment->id]);

        $response->assertUnauthorized();
        $this->assertDatabaseHas('task_comment', ['id' => $this->comment->id, 'deleted_at' => null]);
    }
}
