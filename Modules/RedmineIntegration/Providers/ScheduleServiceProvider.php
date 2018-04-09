<?php

namespace Modules\RedmineIntegration\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot()
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('redmine-synchronize:users')->cron('*/15 * * * *')->withoutOverlapping();
            $schedule->command('redmine-synchronize:projects')->cron('*/15 * * * *')->withoutOverlapping();
            $schedule->command('redmine-synchronize:tasks')->cron('*/15 * * * *')->withoutOverlapping();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
