<?php

namespace Modules\TrelloIntegration\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\TrelloIntegration\Entities\Settings;
use Nwidart\Modules\Facades\Module;

class ScheduleServiceProvider extends ServiceProvider
{
    private const DAYLY = 'daily';
    private const WEEKLY = 'weekly';
    private const MONTHLY = 'monthly';

    public static function isEnabled() {
        return Module::isEnabled('TrelloIntegration');
    }

    public function boot()
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('trello:sync-tasks')->everyFiveMinutes()->withoutOverlapping()->when([static::class, 'isEnabled']);

            $schedule->command('trello:sync-time')->daily()->when(fn(Settings $settings) =>
                $settings->getTimeSyncPeriod() === self::DAYLY && static::isEnabled()
            );

            $schedule->command('trello:sync-time')->weekly()->when(fn(Settings $settings) =>
                $settings->getTimeSyncPeriod() === self::WEEKLY && static::isEnabled()
            );

            $schedule->command('trello:sync-time')->monthly()->when(fn(Settings $settings) =>
                $settings->getTimeSyncPeriod() === self::MONTHLY && static::isEnabled()
            );
        });
    }
}
