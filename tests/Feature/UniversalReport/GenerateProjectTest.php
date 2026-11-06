<?php

namespace Tests\Feature\UniversalReport;

use App\Enums\UniversalReportBase;
use App\Enums\UniversalReportType;
use App\Models\UniversalReport;
use App\Models\User;
use Tests\Facades\IntervalFactory;
use Tests\Facades\ProjectFactory;
use Tests\Facades\TaskFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class GenerateProjectTest extends TestCase
{
    private const URI = '/api/report/universal-report/generate';

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
    }

    private function createReport(array $dataObjects): UniversalReport
    {
        $report = new UniversalReport([
            'name' => 'Project report with charts',
            'type' => UniversalReportType::COMPANY,
            'base' => UniversalReportBase::PROJECT,
            'data_objects' => $dataObjects,
            'fields' => ['base' => ['name'], 'tasks' => [], 'users' => [], 'calculations' => []],
            'charts' => UniversalReportBase::PROJECT->charts(),
        ]);
        $report->user_id = $this->admin->id;
        $report->save();

        return $report;
    }

    private function generate(UniversalReport $report)
    {
        return $this->actingAs($this->admin)->postJson(self::URI, [
            'id' => $report->id,
            'start_at' => now()->subDays(2)->toDateString(),
            'end_at' => now()->toDateString(),
        ]);
    }

    public function test_project_without_users(): void
    {
        $project = ProjectFactory::create();

        $response = $this->generate($this->createReport([$project->id]));

        $response->assertOk();
        $response->assertJsonPath('data.reportData.' . $project->id . '.name', $project->name);
        $response->assertJsonPath('data.reportCharts.total_spent_time_day.datasets', []);
        $response->assertJsonPath('data.reportCharts.total_spent_time_day_and_users_separately.datasets', []);
    }

    public function test_no_matching_projects(): void
    {
        $response = $this->generate($this->createReport([0]));

        $response->assertOk();
        $response->assertJsonPath('data.reportCharts.total_spent_time_day.datasets', []);
    }

    public function test_project_with_users(): void
    {
        $user = UserFactory::refresh()->create();
        $project = ProjectFactory::forUsers([$user])->create();
        $task = TaskFactory::forProject($project)->forUser($user)->create();
        $interval = IntervalFactory::forUser($user)->forTask($task)->create();
        $day = now()->subDay()->startOfDay()->addHours(10);
        $interval->forceFill([
            'start_at' => $day,
            'end_at' => $day->copy()->addHour(),
        ])->save();
        $date = $day->toDateString();

        $response = $this->generate($this->createReport([$project->id]));

        $response->assertOk();
        $response->assertJsonPath('data.reportData.' . $project->id . '.name', $project->name);
        $charts = $response->json('data.reportCharts');
        $projectDataset = $charts['total_spent_time_day']['datasets'][$project->id];
        $this->assertSame($project->name, $projectDataset['label']);
        $this->assertEquals(3600, $projectDataset['data'][$date]);

        $userDataset = $charts['total_spent_time_day_and_users_separately']['datasets'][$project->id][$user->id];
        $this->assertSame($user->full_name, $userDataset['label']);
        $this->assertSame($project->name, $userDataset['projectLabel']);
        $this->assertEquals(3600, $userDataset['data'][$date]);
    }

    public function test_user_chart_ignores_projects_outside_the_selection(): void
    {
        $user = UserFactory::refresh()->create();
        $selected = ProjectFactory::forUsers([$user])->create();
        $other = ProjectFactory::forUsers([$user])->create();
        $day = now()->subDay()->startOfDay()->addHours(10);

        foreach ([$selected, $other] as $project) {
            $task = TaskFactory::forProject($project)->forUser($user)->create();
            IntervalFactory::forUser($user)->forTask($task)->create()->forceFill([
                'start_at' => $day,
                'end_at' => $day->copy()->addHour(),
            ])->save();
        }

        $response = $this->generate($this->createReport([$selected->id]));

        $response->assertOk();
        $datasets = $response->json('data.reportCharts.total_spent_time_day_and_users_separately.datasets');
        $this->assertSame([$selected->id], array_keys($datasets));
    }
}
