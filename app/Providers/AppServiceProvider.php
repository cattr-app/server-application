<?php

namespace App\Providers;

use App;
use App\Models\Property;
use Illuminate\Support\ServiceProvider;
use Laravel\Tinker\TinkerServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Property::loadMorphMap();
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (config('app.debug') && App::environment(['local', 'staging'])) {
            $this->app->register(TelescopeServiceProvider::class);
            $this->app->register(TinkerServiceProvider::class);
        }
    }
}
