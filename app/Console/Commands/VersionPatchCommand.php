<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\Version;

class VersionPatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:version:patch {type=patch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change Cattr version';

    protected Version $version;

    protected function incrementType(): void
    {
        $incrementType = 'increment' . ucfirst($this->argument('type'));
        $this->version->$incrementType();
    }

    protected function initVersion(): void
    {
        $this->version = new Version();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->initVersion();

        if ($this->argument('type') === 'release') {
            $this->version->clearPre();
        } elseif ($this->argument('type') === 'pre') {
            $this->version->incrementPre();
        } else {
            $this->incrementType();
        }

        $this->info("Version changed to $this->version");
    }
}
