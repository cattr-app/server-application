<?php

namespace App\Providers;

use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use CatEvent;
use App\Observers\AttachmentObserver;

class CatEventServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
    }

    public function register(): void
    {
         $this->app->scoped('catevent', static function ($app) {
            return (new Dispatcher($app))->setQueueResolver(static function () use ($app) {
                return $app->make(QueueFactoryContract::class);
            });
        });
    }

    public function provides(): array
    {
        return ['catevent'];
    }
}
