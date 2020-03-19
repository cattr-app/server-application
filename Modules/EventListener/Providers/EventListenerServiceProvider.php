<?php

namespace Modules\EventListener\Providers;

use App\EventFilter\EventServiceProvider as ServiceProvider;
use Config;
use Illuminate\Database\Eloquent\Factory;

class EventListenerServiceProvider extends ServiceProvider
{
    protected $listen = [
        'request.item.create.*' => [
            'Modules\EventListener\Listeners\EventCreateItemObserver@request',
        ],
        'validation.item.create.*' => [
            'Modules\EventListener\Listeners\EventCreateItemObserver@validate',
        ],
        'answer.error.item.create.*' => [
            'Modules\EventListener\Listeners\EventCreateItemObserver@answerError',
        ],
        'item.create.*' => [
            'Modules\EventListener\Listeners\EventCreateItemObserver@action',
        ],
        'answer.success.item.create.*' => [
            'Modules\EventListener\Listeners\EventCreateItemObserver@answerSuccess',
        ],
        'request.item.show.*' => [
            'Modules\EventListener\Listeners\EventShowItemObserver@request',
        ],
        'answer.success.item.show.*' => [
            'Modules\EventListener\Listeners\EventShowItemObserver@answerSuccess',
        ],
        'request.item.edit.*' => [
            'Modules\EventListener\Listeners\EventEditItemObserver@request',
        ],
        'validation.item.edit*' => [
            'Modules\EventListener\Listeners\EventEditItemObserver@validate',
        ],
        'answer.error.item.edit.*' => [
            'Modules\EventListener\Listeners\EventEditItemObserver@answerError',
        ],
        'item.edit.*' => [
            'Modules\EventListener\Listeners\EventEditItemObserver@action',
        ],
        'answer.success.item.edit.*' => [
            'Modules\EventListener\Listeners\EventEditItemObserver@answerSuccess',
        ],
        'request.item.list.*' => [
            'Modules\EventListener\Listeners\EventListItemObserver@request',
        ],
        'answer.success.item.list.*' => [
            'Modules\EventListener\Listeners\EventListItemObserver@answerSuccess',
        ],
        'request.item.remove.*' => [
            'Modules\EventListener\Listeners\EventRemoveItemObserver@request',
        ],
        'answer.success.item.remove.*' => [
            'Modules\EventListener\Listeners\EventRemoveItemObserver@answerSuccess',
        ],
    ];

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        parent::boot();
    }

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/eventlistener');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'eventlistener');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'eventlistener');
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('eventlistener.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'eventlistener'
        );
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/eventlistener');

        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ], 'views');

        $this->loadViewsFrom(array_merge(array_map(static function ($path) {
            return $path . '/modules/eventlistener';
        }, Config::get('view.paths')), [$sourcePath]), 'eventlistener');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories(): void
    {
        if (!app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }
}
