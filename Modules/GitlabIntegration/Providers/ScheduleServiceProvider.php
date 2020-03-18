<?php

namespace Modules\GitlabIntegration\Providers;

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
        return Module::isEnabled('GitlabIntegration');
    }

    public function boot()
    {
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('gitlab:sync')->everyFiveMinutes()->withoutOverlapping()->when([static::class, 'isEnabled']);
            $schedule->command('gitlab:time:sync')->everyMinute()->withoutOverlapping()->when([static::class, 'isEnabled']);
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
