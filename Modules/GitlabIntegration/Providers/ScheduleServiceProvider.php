<?php

namespace Modules\GitlabIntegration\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot(Schedule $schedule): void
    {
        $this->app->booted(static function () use ($schedule) {
            $schedule->command('gitlab:sync')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('gitlab:time:sync')->everyMinute()->withoutOverlapping();
        });
    }
}
