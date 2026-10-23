<?php

namespace Tests\Unit\Regression;

use App\Helpers\ReportHelper;
use PHPUnit\Framework\TestCase;

class ReportDurationCompatibilityTest extends TestCase
{
    public function test_same_day_duration_is_unchanged(): void
    {
        $interval = (object) [
            'start_at' => '2026-10-20 09:15:00',
            'end_at' => '2026-10-20 09:17:05',
            'duration' => 125,
        ];

        $this->assertSame(
            ['2026-10-20' => 125],
            ReportHelper::getIntervalDurationByDay($interval, 'UTC')
        );
    }

    public function test_cross_midnight_duration_is_positive_on_both_days(): void
    {
        // 23:58:40 -> 00:02:10 = 80 + 130 seconds, not signed differences.
        $interval = (object) [
            'start_at' => '2026-10-20 23:58:40',
            'end_at' => '2026-10-21 00:02:10',
            'duration' => 210,
        ];

        $this->assertSame(
            ['2026-10-20' => 80, '2026-10-21' => 130],
            ReportHelper::getIntervalDurationByDay($interval, 'UTC')
        );
    }

    public function test_split_uses_users_timezone_instead_of_company_date(): void
    {
        // 21:55-22:05 UTC is 23:55-00:05 in Berlin on these dates.
        $interval = (object) [
            'start_at' => '2026-10-20 21:55:00',
            'end_at' => '2026-10-20 22:05:00',
            'duration' => 600,
        ];

        $this->assertSame(
            ['2026-10-20' => 300, '2026-10-21' => 300],
            ReportHelper::getIntervalDurationByDay($interval, 'UTC', 'Europe/Berlin')
        );
    }
}
