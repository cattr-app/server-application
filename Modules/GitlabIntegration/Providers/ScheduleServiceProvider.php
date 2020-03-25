<?php

namespace Modules\GitlabIntegration\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('gitlab:sync')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('gitlab:time:sync')->everyMinute()->withoutOverlapping();
        });
    }
}
