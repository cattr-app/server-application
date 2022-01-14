<?php

namespace App\Providers;

use Broadcast;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Broadcast::routes();
        require base_path('routes/channels.php');
    }
}
