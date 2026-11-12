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

class CalendarTest extends TestCase
{
    private const URI = 'tasks/calendar';

    // Tuesday to Thursday, the report is widened to the whole week: Monday 2026-03-09 to Sunday 2026-03-15
    private const START_AT = '2026-03-10';
    private const END_AT = '2026-03-12';

    private Project $project;
    private Task $inside;
    private Task $dueDateOnly;
    private Task $spanning;
    private Task $before;
    private Task $after;
    private Task $undated;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project = ProjectFactory::create();

        $this->inside = $this->task(['start_date' => '2026-03-10', 'due_date' => '2026-03-11']);
        $this->dueDateOnly = $this->task(['due_date' => '2026-03-13']);
        $this->spanning = $this->task(['start_date' => '2026-02-01', 'due_date' => '2026-04-01']);
        $this->before = $this->task(['start_date' => '2026-02-01', 'due_date' => '2026-02-05']);
        $this->after = $this->task(['start_date' => '2026-03-16', 'due_date' => '2026-03-20']);
        $this->undated = $this->task([]);
    }

    private function task(array $dates, ?Project $project = null): Task
    {
        $task = TaskFactory::refresh()->forProject($project ?? $this->project)->create();
        $task->update($dates);

        return $task;
    }

    private function calendar(User $user, array $data = []): TestResponse
    {
        return $this->actingAs($user)->postJson(self::URI, $data + [
            'start_at' => self::START_AT,
            'end_at' => self::END_AT,
        ]);
    }

    private function taskIds(TestResponse $response): array
    {
        $ids = array_map('intval', array_keys($response->json('data.tasks')));
        sort($ids);

        return $ids;
    }

    private function sortedIds(Task ...$tasks): array
    {
        $ids = array_map(static fn(Task $task) => $task->id, $tasks);
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
    public function test_calendar_returns_tasks_overlapping_the_week(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $response = $this->calendar($user, ['project_id' => $this->project->id]);

        $response->assertOk();
        $this->assertSame(
            $this->sortedIds($this->inside, $this->dueDateOnly, $this->spanning),
            $this->taskIds($response)
        );
    }

    public function test_response_structure(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->calendar($admin, ['project_id' => $this->project->id]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'tasks' => [
                        $this->inside->id => [
                            'id',
                            'task_name',
                            'project_id',
                            'status_id',
                            'priority_id',
                            'start_date',
                            'due_date',
                            'status',
                            'priority',
                        ],
                    ],
                    'tasks_by_day' => [['date', 'day', 'month', 'task_ids']],
                    'tasks_by_week' => [['days' => [['day', 'month']], 'tasks' => [['task_id', 'start_week_day', 'end_week_day']]]],
                ],
            ])
            ->assertJsonPath('data.tasks.' . $this->inside->id . '.start_date', '2026-03-10')
            ->assertJsonPath('data.tasks.' . $this->inside->id . '.due_date', '2026-03-11');
        $this->assertCount(7, $response->json('data.tasks_by_day'));
        $this->assertCount(1, $response->json('data.tasks_by_week'));
    }

    public function test_range_across_a_month_boundary_is_split_into_weeks(): void
    {
        // Monday 2026-03-30 to Sunday 2026-04-05 and Monday 2026-04-06 to Sunday 2026-04-12
        $project = ProjectFactory::refresh()->create();
        $firstWeek = $this->task(['start_date' => '2026-03-31', 'due_date' => '2026-04-02'], $project);
        $secondWeek = $this->task(['start_date' => '2026-04-07', 'due_date' => '2026-04-09'], $project);
        $both = $this->task(['start_date' => '2026-04-02', 'due_date' => '2026-04-08'], $project);
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->calendar($admin, [
            'project_id' => $project->id,
            'start_at' => '2026-04-01',
            'end_at' => '2026-04-07',
        ]);

        $response->assertOk();
        $weeks = $response->json('data.tasks_by_week');
        $this->assertCount(2, $weeks);

        $this->assertSame(
            [[30, 3], [31, 3], [1, 4], [2, 4], [3, 4], [4, 4], [5, 4]],
            array_map(static fn(array $day) => [$day['day'], $day['month']], $weeks[0]['days'])
        );
        $this->assertSame(
            [[6, 4], [7, 4], [8, 4], [9, 4], [10, 4], [11, 4], [12, 4]],
            array_map(static fn(array $day) => [$day['day'], $day['month']], $weeks[1]['days'])
        );

        $this->assertEqualsCanonicalizing(
            [
                ['task_id' => $firstWeek->id, 'start_week_day' => 1, 'end_week_day' => 3],
                ['task_id' => $both->id, 'start_week_day' => 3, 'end_week_day' => 6],
            ],
            $weeks[0]['tasks']
        );
        $this->assertEqualsCanonicalizing(
            [
                ['task_id' => $both->id, 'start_week_day' => 0, 'end_week_day' => 2],
                ['task_id' => $secondWeek->id, 'start_week_day' => 1, 'end_week_day' => 3],
            ],
            $weeks[1]['tasks']
        );

        $days = array_column($response->json('data.tasks_by_day'), 'task_ids', 'date');
        $this->assertCount(14, $days);
        $this->assertSame([$firstWeek->id], $days['2026-03-31']);
        $this->assertEqualsCanonicalizing([$firstWeek->id, $both->id], $days['2026-04-02']);
        $this->assertSame([$both->id], $days['2026-04-06']);
        $this->assertEqualsCanonicalizing([$secondWeek->id, $both->id], $days['2026-04-08']);
        $this->assertSame([$secondWeek->id], $days['2026-04-09']);
        $this->assertSame([], $days['2026-04-12']);
    }

    public function test_range_is_widened_to_whole_weeks(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->calendar($admin, ['project_id' => $this->project->id]);

        $days = array_column($response->json('data.tasks_by_day'), 'date');
        $this->assertSame('2026-03-09', $days[0]);
        $this->assertSame('2026-03-15', $days[6]);
    }

    public function test_tasks_are_placed_on_their_days(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->calendar($admin, ['project_id' => $this->project->id]);

        $byDay = array_column($response->json('data.tasks_by_day'), 'task_ids', 'date');
        $this->assertSame([$this->spanning->id], $byDay['2026-03-09']);
        $this->assertEqualsCanonicalizing([$this->spanning->id, $this->inside->id], $byDay['2026-03-10']);
        $this->assertEqualsCanonicalizing([$this->spanning->id, $this->inside->id], $byDay['2026-03-11']);
        $this->assertEqualsCanonicalizing([$this->spanning->id, $this->dueDateOnly->id], $byDay['2026-03-13']);
    }

    public function test_task_with_only_one_date_takes_it_as_both_dates(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->calendar($admin, ['project_id' => $this->project->id]);

        $response->assertJsonPath('data.tasks.' . $this->dueDateOnly->id . '.start_date', '2026-03-13')
            ->assertJsonPath('data.tasks.' . $this->dueDateOnly->id . '.due_date', '2026-03-13');

        $weekTasks = collect($response->json('data.tasks_by_week.0.tasks'))->keyBy('task_id');
        $this->assertSame(['task_id' => $this->inside->id, 'start_week_day' => 1, 'end_week_day' => 2], $weekTasks[$this->inside->id]);
        $this->assertSame(['task_id' => $this->dueDateOnly->id, 'start_week_day' => 4, 'end_week_day' => 4], $weekTasks[$this->dueDateOnly->id]);
    }

    public function test_project_filter_accepts_an_id_or_a_list(): void
    {
        $otherProject = ProjectFactory::refresh()->create();
        $foreign = $this->task(['start_date' => '2026-03-10', 'due_date' => '2026-03-10'], $otherProject);
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $single = $this->calendar($admin, ['project_id' => $otherProject->id]);
        $this->assertSame([$foreign->id], $this->taskIds($single->assertOk()));

        $list = $this->calendar($admin, ['project_id' => [$otherProject->id, $this->project->id]]);
        $this->assertSame(
            $this->sortedIds($this->inside, $this->dueDateOnly, $this->spanning, $foreign),
            $this->taskIds($list->assertOk())
        );
    }

    public function test_without_project_filter_tasks_of_all_projects_are_returned(): void
    {
        $foreign = $this->task(['start_date' => '2026-03-10', 'due_date' => '2026-03-10'], ProjectFactory::refresh()->create());
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $ids = $this->taskIds($this->calendar($admin)->assertOk());

        $this->assertContains($foreign->id, $ids);
        $this->assertContains($this->inside->id, $ids);
        $this->assertNotContains($this->before->id, $ids);
        $this->assertNotContains($this->after->id, $ids);
        $this->assertNotContains($this->undated->id, $ids);
    }

    public function test_task_assignee_sees_only_the_assigned_tasks(): void
    {
        $assignee = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->inside->users()->attach($assignee->id);

        $response = $this->calendar($assignee, ['project_id' => $this->project->id]);

        $this->assertSame([$this->inside->id], $this->taskIds($response->assertOk()));
    }

    public function test_project_member_sees_the_tasks_of_the_project(): void
    {
        $member = UserFactory::refresh()->asUser()->withTokens()->create();
        $member->projects()->attach($this->project->id, ['role_id' => 2]);

        $response = $this->calendar($member, ['project_id' => $this->project->id]);

        $this->assertSame(
            $this->sortedIds($this->inside, $this->dueDateOnly, $this->spanning),
            $this->taskIds($response->assertOk())
        );
    }

    public function test_outsider_sees_no_tasks(): void
    {
        $outsider = UserFactory::refresh()->asUser()->withTokens()->create();

        $response = $this->calendar($outsider, ['project_id' => $this->project->id]);

        $response->assertOk();
        $this->assertSame([], $this->taskIds($response));
    }

    public static function invalidData(): array
    {
        return [
            'missing start_at' => [['start_at' => null]],
            'missing end_at' => [['end_at' => null]],
            'invalid start_at' => [['start_at' => 'tomorrow-ish']],
            'invalid end_at' => [['end_at' => 'abc']],
            'end_at before start_at' => [['start_at' => '2026-03-12', 'end_at' => '2026-03-10']],
            'non-integer project_id' => [['project_id' => 'abc']],
            'non-existent project_id' => [['project_id' => 999999]],
            'non-integer project_id in list' => [['project_id' => ['abc']]],
            'non-existent project_id in list' => [['project_id' => [999999]]],
        ];
    }

    #[DataProvider('invalidData')]
    public function test_validation(array $data): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->calendar($admin, $data);

        $response->assertValidationError();
    }

    public function test_same_day_range_is_valid(): void
    {
        $admin = UserFactory::asAdmin()->withTokens()->create();

        $response = $this->calendar($admin, ['start_at' => self::START_AT, 'end_at' => self::START_AT]);

        $response->assertOk();
    }

    public function test_unauthorized(): void
    {
        $response = $this->postJson(self::URI, ['start_at' => self::START_AT, 'end_at' => self::END_AT]);

        $response->assertUnauthorized();
    }
}
