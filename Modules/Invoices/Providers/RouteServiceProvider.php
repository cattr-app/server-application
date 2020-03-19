<?php

namespace Modules\Invoices\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        Route::middleware('api')
            ->group(__DIR__ . '/../Http/routes.php');
    }
}

