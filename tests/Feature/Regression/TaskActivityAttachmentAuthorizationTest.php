<?php

namespace Tests\Feature\Regression;

use App\Enums\AttachmentStatus;
use App\Models\Attachment;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\TaskHistory;
use App\Models\User;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Storage;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

/**
 * tasks/activity must return the activity of the authorized task only, and attachmentsToRemove must only
 * delete attachments of the edited record or the caller's own uploads that are not attached yet.
 */
class TaskActivityAttachmentAuthorizationTest extends TestCase
{
    private User $member;
    private User $victim;
    private Task $ownTask;
    private Task $foreignTask;
    private TaskComment $ownComment;
    private TaskComment $foreignComment;
    private Attachment $foreignAttachment;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(AttachmentService::DISK);

        $this->member = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->victim = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->ownTask = TaskFactory::refresh()->create();
        $this->member->projects()->attach($this->ownTask->project_id, ['role_id' => 2]);

        $this->foreignTask = TaskFactory::refresh()->create();
        $this->victim->projects()->attach($this->foreignTask->project_id, ['role_id' => 1]);

        $this->ownComment = $this->comment($this->ownTask, $this->member);
        $this->foreignComment = $this->comment($this->foreignTask, $this->victim);
        $this->foreignAttachment = $this->attachment($this->foreignComment, $this->victim);

        foreach ([$this->ownTask, $this->foreignTask] as $task) {
            TaskHistory::create([
                'task_id' => $task->id,
                'user_id' => $this->victim->id,
                'field' => 'task_name',
                'old_value' => 'old',
                'new_value' => 'new',
            ]);
        }
    }

    private function comment(Task $task, User $user): TaskComment
    {
        return TaskComment::create(['task_id' => $task->id, 'user_id' => $user->id, 'content' => 'text']);
    }

    private function attachment(?TaskComment $parent, User $owner, AttachmentStatus $status = AttachmentStatus::GOOD): Attachment
    {
        $attachment = Attachment::create([
            'attachmentable_id' => $parent?->id,
            'attachmentable_type' => $parent?->getMorphClass(),
            'original_name' => 'notes.txt',
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'size' => 5,
            'project_id' => $parent ? $parent->task->project_id : null,
            'user_id' => $owner->id,
            'status' => $status,
        ]);

        Storage::disk(AttachmentService::DISK)->put(app(AttachmentService::class)->getPath($attachment), 'notes');

        return $attachment;
    }

    private function activity(array $extra = [])
    {
        return $this->actingAs($this->member)->postJson('tasks/activity', array_merge([
            'page' => 1,
            'orderBy' => ['created_at', 'desc'],
            'task_id' => $this->ownTask->id,
            'type' => 'all',
        ], $extra));
    }

    private function taskIds($response): array
    {
        return collect($response->json('data'))->pluck('task_id')->unique()->values()->all();
    }

    public function test_activity_of_own_task(): void
    {
        $response = $this->activity()->assertOk();

        $this->assertSame([$this->ownTask->id], $this->taskIds($response));
        $this->assertCount(2, $response->json('data'));
    }

    public function test_activity_ignores_where_for_another_task(): void
    {
        $response = $this->activity(['where' => ['task_id' => ['=', [$this->foreignTask->id]]]])->assertOk();

        $this->assertSame([], $response->json('data'));
    }

    public function test_activity_without_where_is_limited_to_the_authorized_task(): void
    {
        foreach (['all', 'comments', 'history'] as $type) {
            $response = $this->activity(['type' => $type])->assertOk();

            $this->assertSame([$this->ownTask->id], $this->taskIds($response), $type);
        }
    }

    public function test_activity_of_foreign_task_is_forbidden(): void
    {
        $this->activity(['task_id' => $this->foreignTask->id])->assertForbidden();
    }

    public function test_comment_edit_cannot_remove_foreign_attachment(): void
    {
        $this->actingAs($this->member)->postJson('task-comment/edit', [
            'id' => $this->ownComment->id,
            'content' => 'edited',
            'attachmentsToRemove' => [$this->foreignAttachment->id],
        ])->assertOk();

        $this->assertDatabaseHas('attachments', ['id' => $this->foreignAttachment->id]);
        Storage::disk(AttachmentService::DISK)->assertExists(
            app(AttachmentService::class)->getPath($this->foreignAttachment)
        );
    }

    public function test_comment_edit_removes_own_comment_attachment(): void
    {
        $attachment = $this->attachment($this->ownComment, $this->member);

        $this->actingAs($this->member)->postJson('task-comment/edit', [
            'id' => $this->ownComment->id,
            'content' => 'edited',
            'attachmentsToRemove' => [$attachment->id],
        ])->assertOk();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }

    public function test_comment_edit_removes_own_unattached_upload_only(): void
    {
        $own = $this->attachment(null, $this->member, AttachmentStatus::NOT_ATTACHED);
        $foreign = $this->attachment(null, $this->victim, AttachmentStatus::NOT_ATTACHED);

        $this->actingAs($this->member)->postJson('task-comment/edit', [
            'id' => $this->ownComment->id,
            'content' => 'edited',
            'attachmentsToRemove' => [$own->id, $foreign->id],
        ])->assertOk();

        $this->assertDatabaseMissing('attachments', ['id' => $own->id]);
        $this->assertDatabaseHas('attachments', ['id' => $foreign->id]);
    }

    public function test_comment_create_cannot_remove_foreign_attachment(): void
    {
        $this->actingAs($this->member)->postJson('task-comment/create', [
            'task_id' => $this->ownTask->id,
            'content' => 'new',
            'attachmentsToRemove' => [$this->foreignAttachment->id],
        ])->assertOk();

        $this->assertDatabaseHas('attachments', ['id' => $this->foreignAttachment->id]);
    }

    public function test_task_edit_cannot_remove_foreign_attachment(): void
    {
        $this->member->projects()->updateExistingPivot($this->ownTask->project_id, ['role_id' => 1]);

        $this->actingAs($this->member)->postJson('tasks/edit', [
            'id' => $this->ownTask->id,
            'task_name' => 'renamed',
            'project_id' => $this->ownTask->project_id,
            'users' => [$this->member->id],
            'attachmentsToRemove' => [$this->foreignAttachment->id],
        ])->assertOk();

        $this->assertDatabaseHas('attachments', ['id' => $this->foreignAttachment->id]);
    }
}
