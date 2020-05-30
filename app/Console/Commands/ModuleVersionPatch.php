<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\Version;
use InvalidArgumentException;
use JsonException;
use TypeError;

class ModuleVersionPatch extends VersionPatchCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:version:patch {module} {type=patch} {--pre}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';


    /**
     * @throws JsonException
     */
    protected function initVersion(): void
    {
        $this->version = new Version($this->argument('module'));
    }
}
