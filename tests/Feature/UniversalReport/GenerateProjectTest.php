<?php

namespace Tests\Feature\UniversalReport;

use App\Enums\UniversalReportBase;
use App\Enums\UniversalReportType;
use App\Models\UniversalReport;
use App\Models\User;
use Tests\Facades\ProjectFactory;
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

        $response = $this->generate($this->createReport([$project->id]));

        $response->assertOk();
        $response->assertJsonPath('data.reportData.' . $project->id . '.name', $project->name);
        $response->assertJsonPath('data.reportCharts.total_spent_time_day.datasets', []);
    }
}
