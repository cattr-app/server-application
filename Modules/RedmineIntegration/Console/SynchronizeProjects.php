<?php

namespace Modules\RedmineIntegration\Console;

use Illuminate\Console\Command;
use Modules\RedmineIntegration\Helpers\ProjectIntegrationHelper;

/**
 * Class SynchronizeProjects
*/
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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param  ProjectIntegrationHelper  $projectIntegrationHelper
     */
    public function handle(ProjectIntegrationHelper $projectIntegrationHelper)
    {
        $projectIntegrationHelper->synchronizeProjects();
    }
}
