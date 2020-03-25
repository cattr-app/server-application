<?php

namespace Modules\JiraIntegration\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command('jira:sync-tasks')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('jira:sync-time')->everyMinute()->withoutOverlapping();
        });
    }
}
