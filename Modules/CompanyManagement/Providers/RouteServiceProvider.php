<?php

namespace Modules\CompanyManagement\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     */
    protected string  $moduleNamespace = 'Modules\CompanyManagement\Http\Controllers';

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        Route::prefix('v1')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(__DIR__ . '/../Routes/api.php');
    }
}
