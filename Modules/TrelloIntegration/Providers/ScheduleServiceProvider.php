<?php

namespace Modules\TrelloIntegration\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Modules\TrelloIntegration\Entities\Settings;

class ScheduleServiceProvider extends ServiceProvider
{
    private const DAYLY = 'daily';
    private const WEEKLY = 'weekly';
    private const MONTHLY = 'monthly';

    public function boot()
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule->command('trello:sync-tasks')->everyFiveMinutes()->withoutOverlapping();

        $schedule->command('trello:sync-time')->daily()->when(fn(Settings $settings) =>
            $settings->getTimeSyncPeriod() === self::DAYLY
        );

        $schedule->command('trello:sync-time')->weekly()->when(fn(Settings $settings) =>
            $settings->getTimeSyncPeriod() === self::WEEKLY
        );

        $schedule->command('trello:sync-time')->monthly()->when(fn(Settings $settings) =>
            $settings->getTimeSyncPeriod() === self::MONTHLY
        );
    }
}
