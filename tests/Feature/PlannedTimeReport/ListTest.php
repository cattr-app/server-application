<?php

namespace Tests\Feature\PlannedTimeReport;

use App\Models\CronTaskWorkers;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Settings;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;
use Tests\TestResponse;

class ListTest extends TestCase
{
    private const URI = '/api/report/planned-time';

    private const REPORT_DATE = '2026-03-12 04:00:00';

    private User $member;
    private User $worker;
    private Project $project;
    private Project $foreignProject;
    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->member = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->worker = UserFactory::refresh()->asUser()->create();

        $this->project = ProjectFactory::refresh()->forUsers([$this->member])->create();
        $this->foreignProject = ProjectFactory::refresh()->create();

        $this->task = $this->task($this->project, 7200);
        $this->task($this->foreignProject, null);

        // a worker row not created by the cron is not a part of the report
        $this->worker($this->task, 5400, true);
        $this->worker($this->task($this->project, null), 1800, false);
    }

    private function task(Project $project, ?int $estimate): Task
    {
        $task = TaskFactory::refresh()->forProject($project)->create();
        $task->update(['estimate' => $estimate]);

        return $task;
    }

    private function worker(Task $task, int $duration, bool $createdByCron): void
    {
        CronTaskWorkers::query()->insert([
            'user_id' => $this->worker->id,
            'task_id' => $task->id,
            'duration' => $duration,
            'offset' => 0,
            'created_by_cron' => $createdByCron,
        ]);
    }

    private function list(User $user, array $data = []): TestResponse
    {
        return $this->actingAs($user)->postJson(self::URI, $data);
    }

    private function projectIds(TestResponse $response): array
    {
        $ids = array_column($response->json('data.reportData'), 'id');
        sort($ids);

        return $ids;
    }

    public static function globalRoles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
        ];
    }

    #[DataProvider('globalRoles')]
    public function test_global_role_sees_all_projects(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->list($user, ['projects' => [$this->project->id, $this->foreignProject->id]]);

        $response->assertOk();
        $ids = [$this->project->id, $this->foreignProject->id];
        sort($ids);
        $this->assertSame($ids, $this->projectIds($response));
    }

    public function test_user_sees_only_own_projects(): void
    {
        $response = $this->list($this->member, ['projects' => [$this->project->id, $this->foreignProject->id]]);

        $response->assertOk();
        $this->assertSame([$this->project->id], $this->projectIds($response));
    }

    public function test_user_without_projects_gets_empty_report(): void
    {
        $outsider = UserFactory::refresh()->asUser()->withTokens()->create();

        $response = $this->list($outsider, ['projects' => [$this->project->id]]);

        $response->assertOk()->assertJsonPath('data.reportData', []);
    }

    public function test_admin_without_projects_gets_all_projects(): void
    {
        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();

        $response = $this->list($admin);

        $response->assertOk();
        $this->assertContains($this->project->id, $this->projectIds($response));
        $this->assertContains($this->foreignProject->id, $this->projectIds($response));
    }

    public function test_user_without_projects_gets_own_projects(): void
    {
        $response = $this->list($this->member);

        $response->assertOk();
        $this->assertSame([$this->project->id], $this->projectIds($response));
    }

    public function test_response_structure_and_spent_time(): void
    {
        Settings::scope('core.reports')->set('planned_time_report_date', self::REPORT_DATE);

        $response = $this->list($this->member, ['projects' => [$this->project->id]]);

        $response->assertOk()->assertJsonStructure([
            'data' => [
                'reportData' => [
                    '*' => [
                        'id',
                        'name',
                        'total_spent_time',
                        'tasks' => [
                            '*' => [
                                'id', 'task_name', 'due_date', 'estimate', 'project_id', 'total_spent_time', 'workers',
                            ],
                        ],
                    ],
                ],
                'reportDate',
            ],
        ]);
        $response->assertJsonPath('data.reportDate', self::REPORT_DATE);

        $project = $response->json('data.reportData.0');
        $this->assertSame($this->project->id, $project['id']);
        $this->assertSame(5400, $project['total_spent_time']);

        $tasks = array_column($project['tasks'], null, 'id');
        $this->assertCount(2, $tasks);
        $this->assertSame(7200, $tasks[$this->task->id]['estimate']);
        $this->assertSame(5400, $tasks[$this->task->id]['total_spent_time']);
        $this->assertCount(1, $tasks[$this->task->id]['workers']);
        $user = $tasks[$this->task->id]['workers'][0]['user'];
        $this->assertSame(
            [$this->worker->id, $this->worker->full_name, $this->worker->email],
            [$user['id'], $user['full_name'], $user['email']]
        );
        $this->assertSame(5400, $tasks[$this->task->id]['workers'][0]['duration']);
    }

    public function test_report_date_is_null_when_the_time_was_never_calculated(): void
    {
        $response = $this->list($this->member, ['projects' => [$this->project->id]]);

        $response->assertOk()->assertJsonPath('data.reportDate', null);
    }

    public static function invalidPayloads(): array
    {
        return [
            'projects not an array' => [['projects' => 1]],
            'unknown project' => [['projects' => [0]]],
        ];
    }

    #[DataProvider('invalidPayloads')]
    public function test_validation(array $payload): void
    {
        $admin = UserFactory::refresh()->asAdmin()->withTokens()->create();

        $this->list($admin, $payload)->assertValidationError();
    }

    public function test_unauthorized(): void
    {
        $this->postJson(self::URI, [])->assertUnauthorized();
    }
}
