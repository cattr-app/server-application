<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\Version;
use TypeError;

class VersionPatchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:version:patch {type=patch} {--reset}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $type = $this->argument('type');
        try {
            if (!$this->option('reset')) {
                $newVersion = Version::increment($type);
            } else {
                $newVersion = Version::decrement($type);
            }
            $this->info("Version changed to $newVersion");
        } catch (TypeError $error) {
            $this->error($error);
        }
    }
}
