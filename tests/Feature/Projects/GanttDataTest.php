<?php

namespace Tests\Feature\Projects;

use App\Models\CronTaskWorkers;
use App\Models\Project;
use App\Models\ProjectPhase;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class GanttDataTest extends TestCase
{
    private const URI = 'projects/gantt-data';

    private Project $project;
    private ProjectPhase $phase;
    private Task $early;
    private Task $late;
    private Task $undated;
    private Task $dueDateOnly;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = ProjectFactory::create();
        $this->phase = $this->project->phases()->create(['name' => 'Build']);

        $this->late = $this->task([
            'start_date' => '2026-03-10',
            'due_date' => '2026-03-20',
            'project_phase_id' => $this->phase->id,
        ]);
        $this->early = $this->task([
            'start_date' => '2026-03-01',
            'due_date' => '2026-03-05',
            'project_phase_id' => $this->phase->id,
        ]);
        // Tasks without both dates do not belong on the chart
        $this->undated = $this->task([]);
        $this->dueDateOnly = $this->task(['due_date' => '2026-03-30']);

        foreach ([[120, 10], [30, 5]] as [$duration, $offset]) {
            CronTaskWorkers::forceCreate([
                'user_id' => UserFactory::refresh()->create()->id,
                'task_id' => $this->early->id,
                'duration' => $duration,
                'offset' => $offset,
            ]);
        }

        DB::table('tasks_relations')->insert([
            'parent_id' => $this->early->id,
            'child_id' => $this->late->id,
            'label' => 'blocks',
            'weight' => 1,
        ]);
    }

    private function task(array $attributes): Task
    {
        $task = TaskFactory::refresh()->forProject($this->project)->create();
        $task->update($attributes);

        return $task;
    }

    public static function globalRoles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
        ];
    }

    public static function projectRoles(): array
    {
        return [
            'project manager' => [1],
            'project user' => [2],
            'project auditor' => [3],
        ];
    }

    #[DataProvider('globalRoles')]
    public function test_gantt_data_as_global_role(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->actingAs($user)->getJson(self::URI . '?id=' . $this->project->id);

        $response->assertOk();
        $response->assertJsonPath('data.id', $this->project->id);
        $this->assertSame([$this->early->id, $this->late->id], array_column($response->json('data.tasks'), 'id'));
    }

    #[DataProvider('projectRoles')]
    public function test_gantt_data_as_project_member(int $projectRoleId): void
    {
        $user = UserFactory::refresh()->asUser()->withTokens()->create();
        $user->projects()->attach($this->project->id, ['role_id' => $projectRoleId]);

        $response = $this->actingAs($user)->getJson(self::URI . '?id=' . $this->project->id);

        $response->assertOk();
        $this->assertSame([$this->early->id, $this->late->id], array_column($response->json('data.tasks'), 'id'));
    }

    public function test_response_structure(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI . '?id=' . $this->project->id);

        $response->assertOk()->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'tasks' => [[
                    'id',
                    'task_name',
                    'priority_id',
                    'status_id',
                    'estimate',
                    'start_date',
                    'due_date',
                    'project_phase_id',
                    'project_id',
                    'total_spent_time',
                    'total_offset',
                    'status',
                    'priority',
                ]],
                'phases' => [['id', 'name', 'project_id', 'start_date', 'due_date']],
                'tasks_relations' => [['parent_id', 'child_id']],
            ],
        ]);
    }

    public function test_tasks_are_ordered_by_start_date_with_spent_time(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $tasks = $this->actingAs($admin)->getJson(self::URI . '?id=' . $this->project->id)->json('data.tasks');

        [$first, $second] = $tasks;
        $this->assertSame($this->early->id, $first['id']);
        $this->assertSame('2026-03-01', $first['start_date']);
        $this->assertSame('2026-03-05', $first['due_date']);
        $this->assertEquals(150, $first['total_spent_time']);
        $this->assertEquals(15, $first['total_offset']);
        $this->assertSame($this->late->id, $second['id']);
        $this->assertNull($second['total_spent_time']);
    }

    public function test_phase_spans_the_dated_tasks(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI . '?id=' . $this->project->id);

        $response->assertOk();
        $this->assertCount(1, $response->json('data.phases'));
        $response->assertJsonPath('data.phases.0.id', $this->phase->id);
        $response->assertJsonPath('data.phases.0.name', 'Build');
        $response->assertJsonPath('data.phases.0.start_date', '2026-03-01');
        $response->assertJsonPath('data.phases.0.due_date', '2026-03-20');
    }

    public function test_includes_relations_of_the_project_tasks(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI . '?id=' . $this->project->id);

        $response->assertOk();
        $this->assertEquals(
            [['parent_id' => $this->early->id, 'child_id' => $this->late->id]],
            $response->json('data.tasks_relations')
        );
    }

    public function test_project_without_dated_tasks(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $project = ProjectFactory::withTasks(1)->create();

        $response = $this->actingAs($admin)->getJson(self::URI . '?id=' . $project->id);

        $response->assertOk();
        $this->assertSame([], $response->json('data.tasks'));
        $this->assertSame([], $response->json('data.phases'));
    }

    public function test_unauthorized(): void
    {
        $response = $this->getJson(self::URI . '?id=' . $this->project->id);

        $response->assertUnauthorized();
    }

    public function test_without_params(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->actingAs($admin)->getJson(self::URI);

        $response->assertValidationError();
    }

    public function test_unknown_project(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();
        $unknownId = Project::withoutGlobalScopes()->max('id') + 1;

        $response = $this->actingAs($admin)->getJson(self::URI . '?id=' . $unknownId);

        $response->assertValidationError();
    }
}
