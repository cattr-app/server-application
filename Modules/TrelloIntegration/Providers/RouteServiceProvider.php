<?php

namespace Modules\TrelloIntegration\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    public function map()
    {
        Route::middleware('api')
            ->as('v1.integration.trello.')
            ->prefix('v1/integration/trello')
            ->namespace('Modules\TrelloIntegration\Http\Controllers')
            ->group(module_path('TrelloIntegration', '/Routes/api.php'));
    }
}
