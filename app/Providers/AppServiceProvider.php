<?php

namespace App\Providers;

use App;
use App\Models\Property;
use Illuminate\Support\ServiceProvider;
use Laravel\Tinker\TinkerServiceProvider;
use Settings;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Property::loadMorphMap();
        config(['app.timezone' => Settings::scope('core')->get('timezone', date_default_timezone_get())]);
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
