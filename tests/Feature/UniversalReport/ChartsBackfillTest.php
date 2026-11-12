<?php

namespace Tests\Feature\UniversalReport;

use App\Enums\UniversalReportBase;
use App\Enums\UniversalReportType;
use App\Models\UniversalReport;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Tests\Facades\ProjectFactory;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ChartsBackfillTest extends TestCase
{
    private const MIGRATION = '2026_11_12_000000_backfill_null_charts_of_universal_reports.php';

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = UserFactory::refresh()->asUser()->withTokens()->create();
    }

    /** Inserts a report as the old edit endpoint left it: $charts is the raw JSON stored in the column. */
    private function insertReport(string $charts): int
    {
        return DB::table('universal_reports')->insertGetId([
            'user_id' => $this->user->id,
            'name' => 'Legacy report',
            'type' => UniversalReportType::PERSONAL->value,
            'base' => UniversalReportBase::PROJECT->value,
            'data_objects' => json_encode([ProjectFactory::refresh()->create()->id]),
            'fields' => json_encode(['base' => ['name']]),
            'charts' => $charts,
        ]);
    }

    private function runMigration(): void
    {
        /** @var Migration $migration */
        $migration = require database_path('migrations/' . self::MIGRATION);
        $migration->up();
    }

    public function test_json_null_charts_become_an_empty_list(): void
    {
        $legacy = $this->insertReport('null');
        $this->assertNull(UniversalReport::findOrFail($legacy)->charts);

        $this->runMigration();

        $this->assertSame([], UniversalReport::findOrFail($legacy)->charts);
    }

    public function test_reports_with_charts_are_left_untouched(): void
    {
        $withCharts = $this->insertReport('["total_spent_time_day"]');
        $empty = $this->insertReport('[]');

        $this->runMigration();

        $this->assertSame(['total_spent_time_day'], UniversalReport::findOrFail($withCharts)->charts);
        $this->assertSame([], UniversalReport::findOrFail($empty)->charts);
    }

    public function test_migration_is_repeatable(): void
    {
        $legacy = $this->insertReport('null');

        $this->runMigration();
        $this->runMigration();

        $this->assertSame([], UniversalReport::findOrFail($legacy)->charts);
    }

    public function test_migrated_report_can_be_edited_with_the_settings_it_returns(): void
    {
        $legacy = $this->insertReport('null');
        $this->runMigration();

        $show = $this->actingAs($this->user)->getJson("/api/report/universal-report/show?id=$legacy");
        $show->assertOk()->assertJsonPath('data.charts', []);

        $this->actingAs($this->user)->postJson('/api/report/universal-report/edit', [
            'id' => $legacy,
            'name' => 'Migrated report',
            'type' => UniversalReportType::PERSONAL->value,
            'base' => UniversalReportBase::PROJECT->value,
            'dataObjects' => $show->json('data.data_objects'),
            'fields' => $show->json('data.fields'),
            'charts' => $show->json('data.charts'),
        ])->assertOk();

        $this->assertSame('Migrated report', UniversalReport::findOrFail($legacy)->name);
    }
}
