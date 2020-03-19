<?php


namespace Modules\RedmineIntegration\Providers;

use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Register redmine websocket endpoints
     */
    public function boot(): void
    {
        require __DIR__.'/../Routes/channels.php';
    }
}
