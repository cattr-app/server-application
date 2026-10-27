<?php

namespace Tests\Feature\UniversalReport;

use App\Enums\UniversalReportBase;
use App\Enums\UniversalReportType;
use App\Models\UniversalReport;
use App\Models\User;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class IndexTest extends TestCase
{
    private const URI = '/api/report/universal-report';

    private User $admin;
    private User $otherAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = UserFactory::refresh()->asAdmin()->withTokens()->create();
        $this->otherAdmin = UserFactory::refresh()->asAdmin()->withTokens()->create();
    }

    private function createReport(User $owner, UniversalReportType $type): UniversalReport
    {
        $report = new UniversalReport([
            'name' => "$type->value report of user $owner->id",
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

    private function reportIds(array $items): array
    {
        return collect($items)->pluck('id')->sort()->values()->all();
    }

    public function test_admin_gets_company_reports_and_own_personal_reports(): void
    {
        $ownCompany = $this->createReport($this->admin, UniversalReportType::COMPANY);
        $foreignCompany = $this->createReport($this->otherAdmin, UniversalReportType::COMPANY);
        $ownPersonal = $this->createReport($this->admin, UniversalReportType::PERSONAL);
        $this->createReport($this->otherAdmin, UniversalReportType::PERSONAL);

        $response = $this->actingAs($this->admin)->getJson(self::URI);

        $response->assertOk();
        $this->assertSame(
            $this->reportIds([$ownCompany, $foreignCompany]),
            $this->reportIds($response->json('data.company'))
        );
        $this->assertSame([$ownPersonal->id], $this->reportIds($response->json('data.personal')));
    }

    public function test_unauthorized(): void
    {
        $this->getJson(self::URI)->assertUnauthorized();
    }
}
