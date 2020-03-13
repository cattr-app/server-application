<?php

namespace Modules\TrelloIntegration\Console;

use Illuminate\Console\Command;
use Modules\TrelloIntegration\Entities\Settings;
use Modules\TrelloIntegration\Services\SyncTime as Service;

class SyncTime extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'trello:sync-time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize time to Trello for all users, who activate the Trello integration.';

    /**
     * @var Service
     */
    protected Service $service;

    /**
     * @var Settings
     */
    protected Settings $settings;

    /**
     * Create a new command instance.
     *
     * @param Service $service
     * @param Settings $settings
     */
    public function __construct(Service $service, Settings $settings)
    {
        parent::__construct();
        $this->service = $service;
        $this->settings = $settings;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->service->synchronizeAll();
    }
}
