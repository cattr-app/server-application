<?php

namespace App\Providers;

use App\Helpers\FilterDispatcher;
use Illuminate\Contracts\Queue\Factory as QueueFactoryContract;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Filter;
use App\Filters\AttachmentFilter;

class FilterServiceProvider extends ServiceProvider implements DeferrableProvider
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
         $this->app->scoped('filter', static function ($app) {
            return (new FilterDispatcher($app))->setQueueResolver(static function () use ($app) {
                return $app->make(QueueFactoryContract::class);
            });
        });
    }

    public function provides(): array
    {
        return ['filter'];
    }
}
