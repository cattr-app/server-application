<?php

namespace Modules\RedmineIntegration\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{

    protected $namespace = "Modules\\RedmineIntegration\\Http\\Controllers";

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
            ->as('v1.integration.redmine.')
            ->prefix('v1/integration/redmine')
            ->namespace($this->namespace)
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
