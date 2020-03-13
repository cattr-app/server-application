<?php

namespace Modules\JiraIntegration\Console;

use Illuminate\Console\Command;
use Modules\JiraIntegration\Services\SyncTime as Service;

class SyncTime extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'jira:sync-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize time to Jira for all users, who activate the Jira integration.';

    /**
     * @var Service
     */
    protected $service;

    /**
     * Create a new command instance.
     *
     * @param Service $service
     */
    public function __construct(Service $service)
    {
        parent::__construct();

        $this->service = $service;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->service->synchronizeAll();
    }
}
