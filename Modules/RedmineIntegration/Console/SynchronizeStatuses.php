<?php

namespace Modules\RedmineIntegration\Console;

use Illuminate\Console\Command;
use Modules\CompanyManagement\Models\RedmineSettings;
use Redmine\Client;

/**
 * Class SynchronizeStatuses
 *
 * @package Modules\RedmineIntegration\Console
 */
class SynchronizeStatuses extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'redmine-synchronize:statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize statuses from redmine.';

    /**
     * @var RedmineSettings
     */
    protected $companyRedmineSettings;

    /**
     * Create a new command instance.
     *
     * @param  RedmineSettings  $companyRedmineSettings
     */
    public function __construct(RedmineSettings $companyRedmineSettings)
    {
        parent::__construct();

        $this->companyRedmineSettings = $companyRedmineSettings;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->companyRedmineSettings->getURL();
        if (empty($url)) {
            throw new \Exception('Empty URL', 404);
        }

        $key = $this->companyRedmineSettings->getAPIKey();
        if (empty($key)) {
            throw new \Exception('Empty API key', 404);
        }

        $client = new Client($url, $key);
        $redmineStatuses = $client->issue_status->all()['issue_statuses'];
        $savedStatuses = $this->companyRedmineSettings->getStatuses();

        // Merge statuses info from the redmine with the active state of stored statuses
        $statuses = array_map(function ($redmineStatus) use ($savedStatuses) {
            // Try find saved status with the same ID
            $savedStatus = array_first($savedStatuses, function ($savedStatus) use ($redmineStatus) {
                return $savedStatus['id'] === $redmineStatus['id'];
            });

            // Set status is active, if saved status is exist and active,
            // or if status from the Redmine is not closed
            $redmineStatus['is_active'] = isset($savedStatus)
                ? $savedStatus['is_active']
                : !isset($redmineStatus['is_closed']);

            return $redmineStatus;
        }, $redmineStatuses);

        $this->companyRedmineSettings->setStatuses($statuses);
    }
}
