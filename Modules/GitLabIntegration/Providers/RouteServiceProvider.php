<?php

namespace Modules\GitLabIntegration\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected $namespace = "Modules\\GitLabIntegration\\Http\\Controllers";

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Map routes
     */
    public function map()
    {
        $this->mapApiRoutes();
    }

    /**
     * Map Api routes
     */
    protected function mapApiRoutes()
    {
        Route::middleware('api')
            ->as('v1.integration.gitlab.')
            ->prefix('v1/integration/gitlab')
            ->namespace($this->namespace)
            ->group(__DIR__.'/../Routes/api.php');
    }
}
