<?php

namespace App\Console\Commands;

use App\Helpers\Version;
use Illuminate\Console\Command;

class VersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Cattr version';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info(Version::get());
    }
}
