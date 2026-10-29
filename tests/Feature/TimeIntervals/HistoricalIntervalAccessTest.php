<?php

namespace Tests\Feature\TimeIntervals;

use App\Enums\Role;
use App\Models\Project;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Facades\IntervalFactory;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class HistoricalIntervalAccessTest extends TestCase
{
    private User $user;
    private User $colleague;
    private Project $project;
    private TimeInterval $ownInterval;
    private TimeInterval $colleagueInterval;
    private array $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::asUser()->withTokens()->create();
        $this->colleague = UserFactory::asUser()->withTokens()->create();
        $this->project = ProjectFactory::create();

        $task = TaskFactory::forProject($this->project)->forUser($this->user)->create();
        $this->ownInterval = IntervalFactory::forUser($this->user)->forTask($task)->create();
        $this->colleagueInterval = IntervalFactory::forUser($this->colleague)->forTask($task)->create();

        DB::table('projects_users')->updateOrInsert(
            ['project_id' => $this->project->id, 'user_id' => $this->user->id],
            ['role_id' => Role::USER->value],
        );
        DB::table('projects_users')
            ->where('project_id', $this->project->id)
            ->where('user_id', $this->user->id)
            ->delete();

        $this->period = [
            'start_at' => Carbon::parse(min($this->ownInterval->start_at, $this->colleagueInterval->start_at))
                ->startOfDay()->toDateTimeString(),
            'end_at' => Carbon::parse(max($this->ownInterval->end_at, $this->colleagueInterval->end_at))
                ->endOfDay()->toDateTimeString(),
        ];
    }

    public function test_historical_intervals_are_returned_by_all_standard_reports(): void
    {
        $this->actingAs($this->user);

        // The HTTP request must still deny project access after membership removal.
        $this->postJson('projects/show', ['id' => $this->project->id])->assertForbidden();

        $dashboard = $this->postJson('report/dashboard', $this->period + [
            'user_timezone' => 'UTC',
            'users' => [$this->user->id, $this->colleague->id],
        ]);
        $dashboard->assertOk();
        $this->assertSame(
            [$this->ownInterval->id],
            collect($dashboard->json('data.' . $this->user->id))->pluck('id')->all(),
        );
        $this->assertNull($dashboard->json('data.' . $this->colleague->id));

        $projectReport = $this->postJson('report/project', $this->period + [
            'users' => [$this->user->id, $this->colleague->id],
        ]);
        $projectReport->assertOk();
        $projectReport->assertJsonPath('data.0.id', $this->project->id);
        $this->assertSame([$this->user->id], array_column($projectReport->json('data.0.users'), 'id'));

        $timeUseReport = $this->postJson('report/time', $this->period + [
            'users' => [$this->user->id, $this->colleague->id],
        ]);
        $timeUseReport->assertOk();
        $timeUseReport->assertJsonPath('data.0.user.id', $this->user->id);
        $this->assertCount(1, $timeUseReport->json('data'));
    }

    public function test_explicit_project_filters_still_work_for_historical_intervals(): void
    {
        $this->actingAs($this->user);

        $params = $this->period + [
            'user_timezone' => 'UTC',
            'users' => [$this->user->id],
            'projects' => [$this->project->id],
        ];

        $this->postJson('report/dashboard', $params)
            ->assertOk()
            ->assertJsonPath('data.' . $this->user->id . '.0.id', $this->ownInterval->id);
        $this->postJson('report/project', $params)
            ->assertOk()
            ->assertJsonPath('data.0.id', $this->project->id);

        $this->postJson('report/dashboard', array_replace($params, ['projects' => []]))
            ->assertOk()
            ->assertJsonPath('data', []);
        $this->postJson('report/project', array_replace($params, ['projects' => []]))
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_screenshots_and_signed_urls_remain_accessible_to_interval_owner(): void
    {
        $response = $this->actingAs($this->user)->postJson('time-intervals/list', [
            'where' => [
                'task.project_id' => ['in', [$this->project->id]],
                'start_at' => ['between', [$this->period['start_at'], $this->period['end_at']]],
            ],
            'with' => ['task', 'task.project', 'user'],
        ]);

        $response->assertOk();
        $items = $response->json('data.data') ?? $response->json('data');
        $this->assertContains($this->ownInterval->id, array_column($items, 'id'));
        $this->assertNotContains($this->colleagueInterval->id, array_column($items, 'id'));

        $this->actingAs($this->user)->getJson("time-intervals/{$this->ownInterval->id}/screenshot-urls")
            ->assertOk();
        $this->actingAs($this->user)->getJson("time-intervals/{$this->colleagueInterval->id}/screenshot-urls")
            ->assertNotFound('page_not_found');
    }

    public function test_project_managers_can_view_colleague_intervals_only_while_assigned(): void
    {
        $manager = UserFactory::asUser()->withTokens()->create();
        DB::table('projects_users')->insert([
            'project_id' => $this->project->id,
            'user_id' => $manager->id,
            'role_id' => Role::MANAGER->value,
        ]);

        $params = $this->period + [
            'user_timezone' => 'UTC',
            'users' => [$this->user->id, $this->colleague->id],
            'projects' => [$this->project->id],
        ];

        $response = $this->actingAs($manager)->postJson('report/dashboard', $params);
        $response->assertOk();
        $this->assertEqualsCanonicalizing(
            [$this->ownInterval->id, $this->colleagueInterval->id],
            collect($response->json('data'))->flatten(1)->pluck('id')->all(),
        );

        DB::table('projects_users')
            ->where('project_id', $this->project->id)
            ->where('user_id', $manager->id)
            ->delete();

        $this->actingAs($manager)->postJson('report/dashboard', $params)
            ->assertOk()
            ->assertJsonPath('data', []);
    }
}
