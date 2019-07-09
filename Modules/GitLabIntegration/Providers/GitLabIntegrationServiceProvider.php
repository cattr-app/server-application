<?php

namespace Modules\GitLabIntegration\Providers;

use App\EventFilter\EventServiceProvider as ServiceProvider;
use Modules\GitLabIntegration\Console\SyncProjects;

/**
 * Class GitLabIntegrationServiceProvider
 *
 * @package Modules\GitLabIntegration\Providers
 */
class GitLabIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerCommands();

        parent::boot();
    }

    protected function registerCommands()
    {
        $this->commands([
            SyncProjects::class,
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(ScheduleServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
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
