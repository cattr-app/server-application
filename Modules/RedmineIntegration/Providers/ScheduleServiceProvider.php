<?php

namespace Modules\RedmineIntegration\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Facades\Module;

class ScheduleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command('redmine:tasks')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('redmine:projects')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('redmine:users')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('redmine:time')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('redmine:priorities')->everyTenMinutes()->withoutOverlapping();
            $schedule->command('redmine:statuses')->everyTenMinutes()->withoutOverlapping();
        });
    }
}
