<?php

namespace App\EventFilter;

use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class EventFilterServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register(): void
    {
        /** @var Application $app */
        $app = $this->app;

        $app->singleton('filter', static function ($app) {
            return (new Dispatcher($app))->setQueueResolver(static function () use ($app) {
                return $app->make(QueueFactoryContract::class);
            });
        });
    }
}
