<?php

namespace Tests\Feature\Tasks;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use MessagePack\MessagePack;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class DownloadProjectsAndTasksTest extends TestCase
{
    private const URI = 'offline-sync/download-projects-and-tasks';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::asAdmin()->withTokens()->create();
    }

    public static function roles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    public static function privilegedRoles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
        ];
    }

    public function test_download_headers(): void
    {
        $response = $this->actingAs($this->admin)->get(self::URI . '/' . $this->admin->id);

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/octet-stream');
        $response->assertHeader('Content-Disposition', 'attachment; filename=ProjectsAndTasks.cattr');
        $this->assertFalse($response->headers->has('0'));
        $this->assertFalse($response->headers->has('1'));
    }

    #[DataProvider('roles')]
    public function test_download_own_projects_and_tasks(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();
        [$project, $task] = $this->assign($user);

        $response = $this->actingAs($user)->get(self::URI . '/' . $user->id);

        $response->assertOk();
        $data = MessagePack::unpack($response->getContent());
        $this->assertSame(['id', 'projects', 'tasks'], array_keys($data));
        $this->assertSame($user->id, $data['id']);
        $this->assertCount(1, $data['projects']);
        $this->assertCount(1, $data['tasks']);
        $this->assertSame(
            [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'source' => 'internal',
                'updated_at' => $project->updated_at->toJSON(),
            ],
            $data['projects'][0]
        );
        $this->assertSame($task->id, $data['tasks'][0]['id']);
        $this->assertSame($project->id, $data['tasks'][0]['project_id']);
        $this->assertSame($task->task_name, $data['tasks'][0]['task_name']);
        $this->assertSame(
            ['id', 'project_id', 'task_name', 'description', 'url', 'priority_id', 'status_id', 'updated_at'],
            array_keys($data['tasks'][0])
        );
    }

    public function test_download_contains_only_the_requested_users_data(): void
    {
        $owner = UserFactory::refresh()->asUser()->withTokens()->create();
        [$project, $task] = $this->assign($owner);
        $this->assign(UserFactory::refresh()->asUser()->create());

        $response = $this->actingAs($this->admin)->get(self::URI . '/' . $owner->id);

        $response->assertOk();
        $data = MessagePack::unpack($response->getContent());
        $this->assertSame($owner->id, $data['id']);
        $this->assertSame([$project->id], array_column($data['projects'], 'id'));
        $this->assertSame([$task->id], array_column($data['tasks'], 'id'));
    }

    #[DataProvider('privilegedRoles')]
    public function test_download_for_another_user(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();
        $other = UserFactory::refresh()->asUser()->create();
        [, $task] = $this->assign($other);

        $response = $this->actingAs($user)->getJson(self::URI . '/' . $other->id);

        $response->assertOk();
        $data = MessagePack::unpack($response->getContent());
        $this->assertSame($other->id, $data['id']);
        $this->assertSame([$task->id], array_column($data['tasks'], 'id'));
    }

    public function test_download_for_another_user_as_user(): void
    {
        $user = UserFactory::asUser()->withTokens()->create();
        $other = UserFactory::refresh()->asUser()->create();
        $this->assign($other);

        $response = $this->actingAs($user)->getJson(self::URI . '/' . $other->id);

        // Users do not see other users at all
        $response->assertNotFound('page_not_found');
    }

    public function test_download_for_user_without_projects(): void
    {
        $user = UserFactory::asUser()->withTokens()->create();

        $response = $this->actingAs($user)->get(self::URI . '/' . $user->id);

        $response->assertOk();
        $this->assertSame(
            ['id' => $user->id, 'projects' => [], 'tasks' => []],
            MessagePack::unpack($response->getContent())
        );
    }

    public function test_download_for_unknown_user(): void
    {
        $unknownId = User::withoutGlobalScopes()->max('id') + 1;

        $response = $this->actingAs($this->admin)->getJson(self::URI . '/' . $unknownId);

        $response->assertNotFound('page_not_found');
    }

    public function test_unauthorized(): void
    {
        $response = $this->get(self::URI . '/' . $this->admin->id);

        $response->assertUnauthorized();
    }

    /**
     * @return array{0: Project, 1: Task}
     */
    private function assign(User $user): array
    {
        $project = ProjectFactory::create();
        $project->users()->attach($user->id, ['role_id' => 2]);
        $task = TaskFactory::refresh()->forProject($project)->forUser($user)->create();

        return [$project->fresh(), $task->fresh()];
    }
}
