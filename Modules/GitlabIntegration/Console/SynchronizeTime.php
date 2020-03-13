<?php


namespace Modules\GitlabIntegration\Console;

use Illuminate\Console\Command;
use Modules\GitlabIntegration\Services\TimeSynchronizer;

class SynchronizeTime extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'gitlab:time:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize time for Gitlab Tasks for all users, who activate the Gitlab integration.';

    /**
     * @var TimeSynchronizer
     */
    protected $timeSynchronizer;

    /**
     * Create a new command instance.
     * @param TimeSynchronizer $timeSynchronizer
     */
    public function __construct(TimeSynchronizer $timeSynchronizer)
    {
        parent::__construct();

        $this->timeSynchronizer = $timeSynchronizer;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->timeSynchronizer->synchronize();
    }
}
