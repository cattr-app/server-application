<?php

namespace Tests\Feature\TaskComments;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ListTest extends TestCase
{
    private const URI = 'task-comment/list';

    private User $admin;
    private User $assignee;
    private User $projectMember;
    private User $outsider;
    private Task $task;
    private TaskComment $comment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->assignee = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectMember = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->outsider = UserFactory::refresh()->asUser()->withTokens()->create();

        $this->task = TaskFactory::forUser($this->assignee)->create();
        Project::find($this->task->project_id)->users()->attach($this->projectMember->id, ['role_id' => 2]);

        // Created by the admin, so visibility does not depend on the comment or task author
        $this->comment = TaskComment::create([
            'task_id' => $this->task->id,
            'user_id' => $this->admin->id,
            'content' => 'Comment',
        ]);
    }

    private function commentIds(User $user): array
    {
        $response = $this->actingAs($user)->postJson(self::URI, ['where' => ['task_id' => $this->task->id]]);

        $response->assertOk();

        return collect($response->json('data'))->pluck('id')->all();
    }

    public function test_admin_sees_comments(): void
    {
        $this->assertSame([$this->comment->id], $this->commentIds($this->admin));
    }

    public function test_task_assignee_sees_comments(): void
    {
        $this->assertSame([$this->comment->id], $this->commentIds($this->assignee));
    }

    public function test_project_member_sees_comments(): void
    {
        $this->assertSame([$this->comment->id], $this->commentIds($this->projectMember));
    }

    public function test_outsider_does_not_see_comments(): void
    {
        $this->assertSame([], $this->commentIds($this->outsider));
    }

    public function test_unauthorized(): void
    {
        $this->postJson(self::URI)->assertUnauthorized();
    }
}
