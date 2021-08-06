<?php

namespace App\Console\Commands;

use App\Models\TrackedApplication;
use Illuminate\Console\Command;

class ClearExpiredTrackedApps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'intervals:apps:clear-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $items = TrackedApplication::where(
            'created_at',
            '<=',
            now()->subDay()->toIso8601String()
        )->withoutGlobalScopes();

        $this->info('Found ' . $items->count() . ' items for deletion');

        $items->delete();

        $this->info('Done');

        return 0;
    }
}
