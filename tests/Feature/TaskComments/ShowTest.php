<?php

namespace Tests\Feature\TaskComments;

use App\Models\TaskComment;
use App\Models\User;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ShowTest extends TestCase
{
    private const URI = 'task-comment/show';

    private User $admin;
    private User $assignee;
    private User $outsider;
    private TaskComment $comment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
        $this->assignee = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->outsider = UserFactory::refresh()->asUser()->withTokens()->create();
        $task = TaskFactory::forUser($this->assignee)->create();

        $this->comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $this->admin->id,
            'content' => 'Comment',
        ]);
    }

    public function test_show(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI . "?id={$this->comment->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $this->comment->id);
        $response->assertJsonPath('data.content', 'Comment');
    }

    public function test_show_as_task_assignee(): void
    {
        $response = $this->actingAs($this->assignee)->getJson(self::URI . "?id={$this->comment->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $this->comment->id);
    }

    public function test_show_comment_on_inaccessible_task(): void
    {
        $response = $this->actingAs($this->outsider)->getJson(self::URI . "?id={$this->comment->id}");

        $response->assertNotFound('page_not_found');
    }

    public function test_without_params(): void
    {
        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI . "?id={$this->comment->id}");

        $response->assertUnauthorized();
    }
}
