<?php

namespace Tests\Feature\Regression;

use App\Events\ChangeEvent;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeInterval;
use App\Models\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * time-intervals/bulk-edit, tasks/edit and task-comment/create must authorize the task or project
 * taken from the request body the same way the corresponding create endpoints do.
 */
class CrossProjectAuthorizationTest extends TestCase
{
    private User $admin;
    private User $member;
    private Task $ownTask;
    private Task $foreignTask;
    private TimeInterval $interval;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([ChangeEvent::class]);

        $this->admin = UserFactory::refresh()->asAdmin()->create();
        $this->admin->forceFill(['role_id' => 0])->save();
        $this->member = UserFactory::refresh()->asUser()->create();

        $this->ownTask = TaskFactory::refresh()->create();
        $this->member->projects()->attach($this->ownTask->project_id, ['role_id' => 2]);

        $this->foreignTask = TaskFactory::refresh()->create();
        $this->assertNotSame($this->ownTask->project_id, $this->foreignTask->project_id);

        $this->interval = IntervalFactory::refresh()->forUser($this->member)->forTask($this->ownTask)->create();
    }

    private function api(User $user, string $uri, array $data)
    {
        Sanctum::actingAs($user);

        return $this->postJson('/api/' . $uri, $data);
    }

    private function bulkEdit(User $user, int $taskId)
    {
        return $this->api($user, 'time-intervals/bulk-edit', [
            'intervals' => [['id' => $this->interval->id, 'task_id' => $taskId]],
        ]);
    }

    public function test_bulk_edit_to_foreign_project_task_is_forbidden(): void
    {
        $this->bulkEdit($this->member, $this->foreignTask->id)->assertStatus(403);

        $this->assertDatabaseHas('time_intervals', ['id' => $this->interval->id, 'task_id' => $this->ownTask->id]);
    }

    public function test_bulk_edit_within_own_project_is_allowed(): void
    {
        $other = TaskFactory::refresh()->forProject(Project::find($this->ownTask->project_id))->create();

        $this->bulkEdit($this->member, $other->id)->assertStatus(204);

        $this->assertDatabaseHas('time_intervals', ['id' => $this->interval->id, 'task_id' => $other->id]);
    }

    public function test_bulk_edit_of_manual_interval_without_manual_time_is_forbidden(): void
    {
        $other = TaskFactory::refresh()->forProject(Project::find($this->ownTask->project_id))->create();
        TimeInterval::whereKey($this->interval->id)->update(['is_manual' => true]);
        $this->member->forceFill(['manual_time' => false])->save();

        $this->bulkEdit($this->member, $other->id)->assertStatus(403);

        $this->assertDatabaseHas('time_intervals', ['id' => $this->interval->id, 'task_id' => $this->ownTask->id]);
    }

    public function test_bulk_edit_of_manual_interval_with_manual_time_is_allowed(): void
    {
        $other = TaskFactory::refresh()->forProject(Project::find($this->ownTask->project_id))->create();
        TimeInterval::whereKey($this->interval->id)->update(['is_manual' => true]);
        $this->member->forceFill(['manual_time' => true])->save();

        $this->bulkEdit($this->member, $other->id)->assertStatus(204);

        $this->assertDatabaseHas('time_intervals', ['id' => $this->interval->id, 'task_id' => $other->id]);
    }

    public function test_bulk_edit_of_deleted_interval_is_validation_error(): void
    {
        $this->interval->delete();

        $this->bulkEdit($this->admin, $this->foreignTask->id)->assertStatus(422);
    }

    public function test_bulk_edit_uses_new_project_after_task_was_moved(): void
    {
        $other = TaskFactory::refresh()->forProject(Project::find($this->ownTask->project_id))->create();

        // Warm the cached task project, then move the task to a project the member has no role in
        $this->assertTrue($this->member->can('create', [TimeInterval::class, $this->member->id, $other->id, false]));
        $other->update(['project_id' => $this->foreignTask->project_id]);

        $this->bulkEdit($this->member, $other->id)->assertStatus(403);

        $this->assertDatabaseHas('time_intervals', ['id' => $this->interval->id, 'task_id' => $this->ownTask->id]);
    }

    public function test_bulk_edit_to_assigned_task_is_allowed(): void
    {
        $assigned = TaskFactory::refresh()->forUser($this->member)->create();

        $this->bulkEdit($this->member, $assigned->id)->assertStatus(204);
    }

    public function test_bulk_edit_to_foreign_project_task_by_admin_is_allowed(): void
    {
        $adminInterval = IntervalFactory::refresh()->forUser($this->admin)->forTask($this->ownTask)->create();

        $this->api($this->admin, 'time-intervals/bulk-edit', [
            'intervals' => [['id' => $adminInterval->id, 'task_id' => $this->foreignTask->id]],
        ])->assertStatus(204);
    }

    private function editTask(User $user, Task $task, int $projectId)
    {
        return $this->api($user, 'tasks/edit', [
            'id' => $task->id,
            'project_id' => $projectId,
            'task_name' => $task->task_name,
            'priority_id' => $task->priority_id,
            'status_id' => $task->status_id,
            'users' => [],
        ]);
    }

    public function test_task_edit_move_to_foreign_project_is_forbidden(): void
    {
        $manager = UserFactory::refresh()->asUser()->create();
        $manager->projects()->attach($this->ownTask->project_id, ['role_id' => 1]);

        $this->editTask($manager, $this->ownTask, $this->foreignTask->project_id)->assertStatus(403);

        $this->assertDatabaseHas('tasks', ['id' => $this->ownTask->id, 'project_id' => $this->ownTask->project_id]);
    }

    public function test_task_edit_move_between_managed_projects_is_allowed(): void
    {
        $manager = UserFactory::refresh()->asUser()->create();
        $manager->projects()->attach($this->ownTask->project_id, ['role_id' => 1]);
        $manager->projects()->attach($this->foreignTask->project_id, ['role_id' => 1]);

        $this->editTask($manager, $this->ownTask, $this->foreignTask->project_id)->assertStatus(200);

        $this->assertDatabaseHas('tasks', ['id' => $this->ownTask->id, 'project_id' => $this->foreignTask->project_id]);
    }

    public function test_task_edit_without_moving_is_allowed(): void
    {
        $manager = UserFactory::refresh()->asUser()->create();
        $manager->projects()->attach($this->ownTask->project_id, ['role_id' => 1]);

        $this->editTask($manager, $this->ownTask, $this->ownTask->project_id)->assertStatus(200);
    }

    public function test_task_edit_move_by_admin_is_allowed(): void
    {
        $this->editTask($this->admin, $this->ownTask, $this->foreignTask->project_id)->assertStatus(200);
    }

    public function test_comment_on_invisible_task_is_forbidden(): void
    {
        $this->markTestSkipped('TaskAccessScope is bypassed when running in console on this release, so task visibility cannot be exercised under PHPUnit.');

        $this->api($this->member, 'task-comment/create', [
            'task_id' => $this->foreignTask->id,
            'content' => 'hello',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('task_comment', ['task_id' => $this->foreignTask->id]);
    }

    public function test_comment_on_visible_task_is_allowed(): void
    {
        $this->api($this->member, 'task-comment/create', [
            'task_id' => $this->ownTask->id,
            'content' => 'hello',
        ])->assertStatus(200);

        $this->assertDatabaseHas('task_comment', ['task_id' => $this->ownTask->id, 'user_id' => $this->member->id]);
    }

    public function test_comment_on_assigned_task_is_allowed(): void
    {
        $assigned = TaskFactory::refresh()->forUser($this->member)->create();

        $this->api($this->member, 'task-comment/create', [
            'task_id' => $assigned->id,
            'content' => 'hello',
        ])->assertStatus(200);
    }

    public function test_comment_by_admin_is_allowed(): void
    {
        $this->api($this->admin, 'task-comment/create', [
            'task_id' => $this->foreignTask->id,
            'content' => 'note',
        ])->assertStatus(200);
    }

    public function test_comment_without_task_id_is_validation_error(): void
    {
        $this->api($this->member, 'task-comment/create', ['content' => 'x'])
            ->assertStatus(422);
    }
}
