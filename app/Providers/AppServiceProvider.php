<?php

namespace App\Providers;

use App;
use App\Models\Property;
use App\Models\TimeInterval;
use App\Models\User;
use App\Observers\TimeIntervalObserver;
use App\Observers\UserObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        User::observe(UserObserver::class);
        TimeInterval::observe(TimeIntervalObserver::class);
        config(['app.timezone' => Settings::scope('core')->get('timezone', date_default_timezone_get())]);

        // Public signed screenshot routes: no user to key by, so per IP
        RateLimiter::for(
            'screenshots',
            static fn (Request $request) => Limit::perMinute(config('app.screenshots_rate_limit'))->by($request->ip())
        );
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        if (config('app.debug') && App::environment(['local', 'staging'])) {
            $this->app->register(TinkerServiceProvider::class);
        }
    }
}
