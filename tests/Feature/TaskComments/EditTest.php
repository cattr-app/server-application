<?php

namespace Tests\Feature\TaskComments;

use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class EditTest extends TestCase
{
    private const URI = 'task-comment/edit';

    private User $admin;
    private User $author;
    private User $colleague;
    private User $outsider;
    private Task $task;
    private TaskComment $comment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->author = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->colleague = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->outsider = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->task = TaskFactory::refresh()->create();
        foreach ([$this->author, $this->colleague] as $member) {
            $member->projects()->attach($this->task->project_id, ['role_id' => 2]);
        }

        $this->comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->author->id,
            'content' => 'original',
        ]);
    }

    private function edit(User $user, ?int $id = null)
    {
        return $this->actingAs($user)->postJson(self::URI, [
            'id' => $id ?? $this->comment->id,
            'content' => 'edited',
        ]);
    }

    public function test_author_can_edit_own_comment(): void
    {
        $this->edit($this->author)->assertOk()->assertJsonPath('data.content', 'edited');

        $this->assertDatabaseHas('task_comment', ['id' => $this->comment->id, 'content' => 'edited']);
    }

    public function test_admin_can_edit_any_comment(): void
    {
        $this->edit($this->admin)->assertOk();

        $this->assertDatabaseHas('task_comment', ['id' => $this->comment->id, 'content' => 'edited']);
        $this->assertDatabaseHas('task_comment', ['id' => $this->comment->id, 'user_id' => $this->author->id]);
    }

    public function test_project_colleague_cannot_edit_foreign_comment(): void
    {
        $this->edit($this->colleague)->assertForbidden();

        $this->assertDatabaseHas('task_comment', ['id' => $this->comment->id, 'content' => 'original']);
    }

    public function test_user_outside_the_project_cannot_edit_foreign_comment(): void
    {
        $this->edit($this->outsider)->assertForbidden();

        $this->assertDatabaseHas('task_comment', ['id' => $this->comment->id, 'content' => 'original']);
    }

    public function test_author_cannot_edit_comment_on_task_that_is_no_longer_visible(): void
    {
        $this->author->projects()->detach($this->task->project_id);

        $this->edit($this->author)->assertForbidden();

        $this->assertDatabaseHas('task_comment', ['id' => $this->comment->id, 'content' => 'original']);
    }

    public function test_unknown_comment_is_not_found(): void
    {
        $this->edit($this->author, 999999)->assertNotFound('page_not_found');
    }

    public function test_non_integer_id_is_rejected_by_validation(): void
    {
        foreach ([[$this->comment->id], (string)$this->comment->id, 'abc'] as $id) {
            $this->actingAs($this->admin)
                ->postJson(self::URI, ['id' => $id, 'content' => 'edited'])
                ->assertValidationError();
        }

        $this->assertDatabaseHas('task_comment', ['id' => $this->comment->id, 'content' => 'original']);
    }

    public function test_edit_keeps_the_author(): void
    {
        $this->edit($this->author)->assertOk();

        $this->assertDatabaseHas('task_comment', ['id' => $this->comment->id, 'user_id' => $this->author->id]);
    }
}
