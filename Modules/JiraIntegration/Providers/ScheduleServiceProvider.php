<?php

namespace Modules\JiraIntegration\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot(Schedule $schedule): void
    {
        $this->app->booted(static function () use ($schedule) {
            $schedule->command('jira:sync-tasks')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('jira:sync-time')->everyMinute()->withoutOverlapping();
        });
    }
}
