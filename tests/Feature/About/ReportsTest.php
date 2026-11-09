<?php

namespace Tests\Feature\About;

use App\Helpers\ReportHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Facades\UserFactory;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    private const URI = 'about/reports';

    public static function roles(): array
    {
        return [
            'admin' => ['asAdmin'],
            'manager' => ['asManager'],
            'auditor' => ['asAuditor'],
            'user' => ['asUser'],
        ];
    }

    public function test_guest_is_unauthorized(): void
    {
        $this->getJson(self::URI)->assertUnauthorized();
    }

    #[DataProvider('roles')]
    public function test_every_role_gets_the_available_report_formats(string $role): void
    {
        $user = UserFactory::refresh()->$role()->withTokens()->create();

        $this->actingAs($user)->getJson(self::URI)
            ->assertOk()
            ->assertJsonPath('data.types', ReportHelper::getAvailableReportFormats())
            ->assertJsonPath('data.types.csv', 'text/csv')
            ->assertJsonPath('data.types.pdf', 'application/pdf');
    }
}
