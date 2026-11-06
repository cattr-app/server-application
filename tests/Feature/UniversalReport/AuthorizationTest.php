<?php

namespace Tests\Feature\UniversalReport;

use App\Enums\UniversalReportBase;
use App\Enums\UniversalReportType;
use App\Models\Project;
use App\Models\UniversalReport;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    private const URI = '/api/report/universal-report';

    private User $admin;
    private User $owner;
    private User $stranger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->owner = UserFactory::refresh()->asUser()->withTokens()->create();
        $this->stranger = UserFactory::refresh()->asUser()->withTokens()->create();
    }

    private function createReport(User $owner, UniversalReportType $type): UniversalReport
    {
        $report = new UniversalReport([
            'name' => "$type->value report of user $owner->id",
            'type' => $type,
            'base' => UniversalReportBase::PROJECT,
            'data_objects' => [],
            'fields' => ['project' => ['name']],
            'charts' => [],
        ]);
        $report->user_id = $owner->id;
        $report->save();

        return $report;
    }

    private function editPayload(UniversalReport $report, string $type, string $name = 'Renamed'): array
    {
        return [
            'id' => $report->id,
            'name' => $name,
            'type' => $type,
            'base' => UniversalReportBase::PROJECT->value,
            'dataObjects' => [Project::factory()->create()->id],
            'fields' => ['project' => ['name']],
            'charts' => [],
        ];
    }

    private function assertUnchanged(UniversalReport $report): void
    {
        $fresh = UniversalReport::find($report->id);

        $this->assertNotNull($fresh);
        $this->assertSame($report->name, $fresh->name);
        $this->assertSame($report->type, $fresh->type);
    }

    public function test_owner_shows_own_personal_report(): void
    {
        $report = $this->createReport($this->owner, UniversalReportType::PERSONAL);

        $this->actingAs($this->owner)->getJson(self::URI . "/show?id=$report->id")
            ->assertOk()
            ->assertJsonPath('data.id', $report->id);
    }

    public function test_user_cannot_show_foreign_personal_report(): void
    {
        $report = $this->createReport($this->owner, UniversalReportType::PERSONAL);

        $this->actingAs($this->stranger)->getJson(self::URI . "/show?id=$report->id")->assertError(403);
    }

    public function test_admin_cannot_show_foreign_personal_report(): void
    {
        $report = $this->createReport($this->owner, UniversalReportType::PERSONAL);

        $this->actingAs($this->admin)->getJson(self::URI . "/show?id=$report->id")->assertError(403);
    }

    public function test_user_shows_accessible_company_report(): void
    {
        $report = $this->createReport($this->admin, UniversalReportType::COMPANY);

        $this->actingAs($this->stranger)->getJson(self::URI . "/show?id=$report->id")->assertOk();
    }

    public function test_owner_edits_own_personal_report(): void
    {
        $report = $this->createReport($this->owner, UniversalReportType::PERSONAL);

        $this->actingAs($this->owner)
            ->postJson(self::URI . '/edit', $this->editPayload($report, 'personal'))
            ->assertOk();

        $this->assertSame('Renamed', UniversalReport::find($report->id)->name);
    }

    public function test_user_cannot_edit_foreign_personal_report(): void
    {
        $report = $this->createReport($this->owner, UniversalReportType::PERSONAL);

        $this->actingAs($this->stranger)
            ->postJson(self::URI . '/edit', $this->editPayload($report, 'personal'))
            ->assertError(403);

        $this->assertUnchanged($report);
    }

    public function test_admin_cannot_edit_foreign_personal_report(): void
    {
        $report = $this->createReport($this->owner, UniversalReportType::PERSONAL);

        $this->actingAs($this->admin)
            ->postJson(self::URI . '/edit', $this->editPayload($report, 'personal'))
            ->assertError(403);

        $this->assertUnchanged($report);
    }

    public function test_user_cannot_downgrade_company_report_to_personal(): void
    {
        $report = $this->createReport($this->admin, UniversalReportType::COMPANY);

        $this->actingAs($this->stranger)
            ->postJson(self::URI . '/edit', $this->editPayload($report, 'personal'))
            ->assertError(403);

        $this->assertUnchanged($report);
    }

    public function test_user_cannot_promote_own_report_to_company(): void
    {
        $report = $this->createReport($this->owner, UniversalReportType::PERSONAL);

        $this->actingAs($this->owner)
            ->postJson(self::URI . '/edit', $this->editPayload($report, 'company'))
            ->assertError(403);

        $this->assertUnchanged($report);
    }

    public function test_admin_edits_company_report(): void
    {
        $report = $this->createReport($this->owner, UniversalReportType::COMPANY);

        $this->actingAs($this->admin)
            ->postJson(self::URI . '/edit', $this->editPayload($report, 'company'))
            ->assertOk();

        $this->assertSame('Renamed', UniversalReport::find($report->id)->name);
    }

    public function test_owner_removes_own_personal_report(): void
    {
        $report = $this->createReport($this->owner, UniversalReportType::PERSONAL);

        $this->actingAs($this->owner)->postJson(self::URI . '/remove', ['id' => $report->id])->assertNoContent();

        $this->assertNull(UniversalReport::find($report->id));
    }

    public function test_user_cannot_remove_foreign_personal_report(): void
    {
        $report = $this->createReport($this->owner, UniversalReportType::PERSONAL);

        $this->actingAs($this->stranger)->postJson(self::URI . '/remove', ['id' => $report->id])->assertError(403);

        $this->assertUnchanged($report);
    }

    public function test_admin_cannot_remove_foreign_personal_report(): void
    {
        $report = $this->createReport($this->owner, UniversalReportType::PERSONAL);

        $this->actingAs($this->admin)->postJson(self::URI . '/remove', ['id' => $report->id])->assertError(403);

        $this->assertUnchanged($report);
    }

    public function test_user_cannot_remove_company_report(): void
    {
        $report = $this->createReport($this->admin, UniversalReportType::COMPANY);

        $this->actingAs($this->stranger)->postJson(self::URI . '/remove', ['id' => $report->id])->assertError(403);

        $this->assertUnchanged($report);
    }

    public function test_admin_removes_company_report(): void
    {
        $report = $this->createReport($this->owner, UniversalReportType::COMPANY);

        $this->actingAs($this->admin)->postJson(self::URI . '/remove', ['id' => $report->id])->assertNoContent();

        $this->assertNull(UniversalReport::find($report->id));
    }

    #[DataProvider('generatingEndpoints')]
    public function test_user_cannot_generate_foreign_personal_report(string $endpoint): void
    {
        $report = $this->createReport($this->owner, UniversalReportType::PERSONAL);

        $this->actingAs($this->stranger)->postJson(self::URI . "/$endpoint", ['id' => $report->id])->assertError(403);
    }

    #[DataProvider('generatingEndpoints')]
    public function test_admin_cannot_generate_foreign_personal_report(string $endpoint): void
    {
        $report = $this->createReport($this->owner, UniversalReportType::PERSONAL);

        $this->actingAs($this->admin)->postJson(self::URI . "/$endpoint", ['id' => $report->id])->assertError(403);
    }

    public static function generatingEndpoints(): array
    {
        return [
            'generate' => ['generate'],
            'download' => ['download'],
        ];
    }

    public function test_index_hides_foreign_personal_reports_from_user(): void
    {
        $company = $this->createReport($this->admin, UniversalReportType::COMPANY);
        $own = $this->createReport($this->stranger, UniversalReportType::PERSONAL);
        $this->createReport($this->owner, UniversalReportType::PERSONAL);

        $response = $this->actingAs($this->stranger)->getJson(self::URI);

        $response->assertOk();
        $this->assertSame([$company->id], collect($response->json('data.company'))->pluck('id')->all());
        $this->assertSame([$own->id], collect($response->json('data.personal'))->pluck('id')->all());
    }
}
