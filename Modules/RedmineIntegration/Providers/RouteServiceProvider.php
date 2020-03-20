<?php

namespace Modules\RedmineIntegration\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleNamespace = "Modules\RedmineIntegration\Http\Controllers";

    /**
     * Map routes
     */
    public function map(): void
    {
        Route::middleware('api')
            ->as('v1.integration.redmine.')
            ->prefix('v1/integration/redmine')
            ->namespace($this->moduleNamespace)
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
