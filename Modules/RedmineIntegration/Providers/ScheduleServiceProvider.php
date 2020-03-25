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
            $schedule->command('redmine-synchronize:tasks')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('redmine-synchronize:projects')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('redmine-synchronize:users')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('redmine-synchronize:time')->everyFiveMinutes()->withoutOverlapping();
            $schedule->command('redmine-synchronize:priorities')->everyFifteenMinutes()->withoutOverlapping();
            $schedule->command('redmine-synchronize:statuses')->everyFifteenMinutes()->withoutOverlapping();
        });
    }
}
