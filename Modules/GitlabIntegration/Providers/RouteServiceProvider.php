<?php

namespace Modules\GitlabIntegration\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     */
    protected string $moduleNamespace = 'Modules\GitlabIntegration\Http\Controllers';

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        Route::middleware('api')
            ->as('v1.integration.gitlab.')
            ->prefix('v1/integration/gitlab')
            ->namespace($this->moduleNamespace)
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
