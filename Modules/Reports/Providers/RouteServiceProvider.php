<?php

namespace Modules\Reports\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     */
    protected string $moduleNamespace = 'Modules\Reports\Http\Controllers';

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        Route::middleware(['api', 'auth:api'])
            ->prefix('v1')
            ->namespace($this->moduleNamespace)
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
