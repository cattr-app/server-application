<?php

namespace Tests\Feature\UniversalReport;

use App\Enums\UniversalReportBase;
use App\Enums\UniversalReportType;
use App\Models\UniversalReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class EditTest extends TestCase
{
    private const URI = '/api/report/universal-report/edit';

    private User $admin;
    private User $user;
    private int $projectId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->projectId = ProjectFactory::create()->id;
    }

    private function createReport(User $owner, UniversalReportType $type): UniversalReport
    {
        $report = new UniversalReport([
            'name' => 'Original',
            'type' => $type,
            'base' => UniversalReportBase::PROJECT,
            'data_objects' => [],
            'fields' => [],
            'charts' => [],
        ]);
        $report->user_id = $owner->id;
        $report->save();

        return $report;
    }

    private function payload(UniversalReport $report, UniversalReportType $type): array
    {
        return [
            'id' => $report->id,
            'name' => 'Edited',
            'type' => $type->value,
            'base' => UniversalReportBase::PROJECT->value,
            'dataObjects' => [$this->projectId],
            'fields' => ['base' => ['name']],
            'charts' => [],
        ];
    }

    private function countReportUpdates(callable $callback): int
    {
        $updates = 0;
        DB::listen(function ($query) use (&$updates) {
            if (str_starts_with(strtolower($query->sql), 'update `universal_reports`')) {
                $updates++;
            }
        });

        $callback();

        return $updates;
    }

    public function test_admin_edits_company_report_with_single_update(): void
    {
        $report = $this->createReport($this->admin, UniversalReportType::COMPANY);

        $updates = $this->countReportUpdates(function () use ($report) {
            $this->actingAs($this->admin)
                ->postJson(self::URI, $this->payload($report, UniversalReportType::COMPANY))
                ->assertOk();
        });

        $this->assertSame(1, $updates);
        $this->assertSame('Edited', $report->fresh()->name);
        $this->assertSame(UniversalReportType::COMPANY, $report->fresh()->type);
    }

    public function test_user_cannot_save_company_report(): void
    {
        $report = $this->createReport($this->admin, UniversalReportType::COMPANY);

        $updates = $this->countReportUpdates(function () use ($report) {
            $this->actingAs($this->user)
                ->postJson(self::URI, $this->payload($report, UniversalReportType::COMPANY))
                ->assertStatus(403);
        });

        $this->assertSame(0, $updates);
        $this->assertSame('Original', $report->fresh()->name);
    }

    public function test_user_edits_own_personal_report_with_single_update(): void
    {
        $report = $this->createReport($this->user, UniversalReportType::PERSONAL);

        $updates = $this->countReportUpdates(function () use ($report) {
            $this->actingAs($this->user)
                ->postJson(self::URI, $this->payload($report, UniversalReportType::PERSONAL))
                ->assertOk();
        });

        $this->assertSame(1, $updates);
        $this->assertSame('Edited', $report->fresh()->name);
    }

    public function test_admin_edits_own_personal_report_with_single_update(): void
    {
        $report = $this->createReport($this->admin, UniversalReportType::PERSONAL);

        $updates = $this->countReportUpdates(function () use ($report) {
            $this->actingAs($this->admin)
                ->postJson(self::URI, $this->payload($report, UniversalReportType::PERSONAL))
                ->assertOk();
        });

        $this->assertSame(1, $updates);
        $this->assertSame('Edited', $report->fresh()->name);
    }

    public function test_charts_can_be_emptied(): void
    {
        $report = $this->createReport($this->user, UniversalReportType::PERSONAL);
        $report->update(['charts' => ['total_spent_time_day']]);

        $this->actingAs($this->user)
            ->postJson(self::URI, ['charts' => []] + $this->payload($report, UniversalReportType::PERSONAL))
            ->assertOk();

        $this->assertSame([], $report->fresh()->charts);
    }

    public function test_charts_are_required(): void
    {
        $report = $this->createReport($this->user, UniversalReportType::PERSONAL);
        $payload = $this->payload($report, UniversalReportType::PERSONAL);
        unset($payload['charts']);

        $this->actingAs($this->user)->postJson(self::URI, $payload)->assertValidationError();

        $this->assertSame('Original', $report->fresh()->name);
    }

    public function test_charts_cannot_be_null(): void
    {
        $report = $this->createReport($this->user, UniversalReportType::PERSONAL);

        $this->actingAs($this->user)
            ->postJson(self::URI, ['charts' => null] + $this->payload($report, UniversalReportType::PERSONAL))
            ->assertValidationError();

        $this->assertSame('Original', $report->fresh()->name);
    }

    public function test_unauthorized(): void
    {
        $this->postJson(self::URI, [])->assertUnauthorized();
    }
}
