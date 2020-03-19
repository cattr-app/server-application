<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (config('app.debug') && $this->app->isLocal()) {
            $this->app->register(TelescopeServiceProvider::class);
        }
    }
}
