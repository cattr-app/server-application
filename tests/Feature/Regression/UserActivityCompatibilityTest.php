<?php

namespace Tests\Feature\Regression;

use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserActivityCompatibilityTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_online_flag_does_not_treat_distant_future_activity_as_recent(): void
    {
        Carbon::setTestNow('2026-10-20 12:00:00');
        config(['app.user_activity.online_status_time' => 60]);

        $recent = new User(['last_activity' => '2026-10-20 11:59:30']);
        $stale = new User(['last_activity' => '2026-10-20 11:58:00']);
        $future = new User(['last_activity' => '2026-10-20 14:00:00']);

        $this->assertTrue($recent->online);
        $this->assertFalse($stale->online);
        $this->assertFalse($future->online);
    }
}
