<?php

namespace App\Console\Commands;

use App\Helpers\Version;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cattr:module:version')]
class ModuleVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:module:version {module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get version of provided module';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info((string)new Version($this->argument('module')));
    }
}
