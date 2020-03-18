<?php

namespace Modules\JiraIntegration\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Nwidart\Modules\Facades\Module;

class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * ScheduleServiceProvider constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        parent::__construct($app);
    }

    public static function isEnabled() {
        return Module::isEnabled('JiraIntegration');
    }

    public function boot()
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('jira:sync-tasks')->everyFiveMinutes()->withoutOverlapping()->when([static::class, 'isEnabled']);
            $schedule->command('jira:sync-time')->everyMinute()->withoutOverlapping()->when([static::class, 'isEnabled']);
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
}
