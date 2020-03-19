<?php

namespace Modules\RedmineIntegration\Console;

use Illuminate\Console\Command;
use Modules\RedmineIntegration\Helpers\ProjectIntegrationHelper;

class SynchronizeProjects extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'redmine-synchronize:projects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize projects from redmine for all users, who activate redmine integration.';


    /**
     * Execute the console command.
     */
    public function handle(ProjectIntegrationHelper $projectIntegrationHelper): void
    {
        $projectIntegrationHelper->synchronizeProjects();
    }
}
