<?php

namespace Modules\GitLabIntegration\Providers;

use App\EventFilter\EventServiceProvider as ServiceProvider;

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
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
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
