<?php

namespace Modules\JiraIntegration\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     */
    protected string $moduleNamespace = 'Modules\JiraIntegration\Http\Controllers';


    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        Route::middleware('api')
            ->as('v1.integration.jira.')
            ->prefix('v1/integration/jira')
            ->namespace($this->moduleNamespace)
            ->group(module_path('JiraIntegration', '/Routes/api.php'));
    }
}
