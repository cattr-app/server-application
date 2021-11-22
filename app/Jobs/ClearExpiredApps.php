<?php

namespace App\Jobs;

use App\Models\TrackedApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ClearExpiredApps implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $uniqueFor = 3600;

    public function handle(): void
    {
        TrackedApplication::where(
            'created_at',
            '<=',
            now()->subDay()->toIso8601String()
        )->withoutGlobalScopes()->each(fn (TrackedApplication $el) => $el->delete());
    }
}
