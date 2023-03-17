<?php

namespace App\Console\Commands;

use App\Jobs\ClearExpiredApps;
use App\Models\TrackedApplication;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cattr:intervals:apps:clear-expired')]
class ClearExpiredTrackedApps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:intervals:apps:clear-expired';

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
        $this->info(
            sprintf(
                'Found %d items for deletion',
                TrackedApplication::where(
                    'created_at',
                    '<=',
                    now()->subDay()->toIso8601String()
                )->withoutGlobalScopes()->count()
            )
        );

        ClearExpiredApps::dispatch();

        $this->info('Clearance job dispatched');

        return 0;
    }
}
