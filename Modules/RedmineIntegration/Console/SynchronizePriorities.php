<?php

namespace Modules\RedmineIntegration\Console;

use Illuminate\Console\Command;
use Log;
use Modules\RedmineIntegration\Models\Priority;

/**
 * Class SynchronizePriorities
*/
class SynchronizePriorities extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'redmine-synchronize:priorities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize priorities from redmine.';

    /**
     * @var Priority
     */
    protected $priority;

    /**
     * Create a new command instance.
     *
     * @param  Priority  $priority
     */
    public function __construct(Priority $priority)
    {
        parent::__construct();

        $this->priority = $priority;
    }

    /**
     * Execute the console command.
    */
    public function handle()
    {
        try {
            $this->priority->synchronize();
        } catch (\Exception $e) {
            Log::error($e);
        }
    }
}
