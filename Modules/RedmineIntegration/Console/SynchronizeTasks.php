<?php

namespace Modules\RedmineIntegration\Console;

use Illuminate\Console\Command;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Helpers\TaskIntegrationHelper;

/**
 * Class SynchronizeTasks
 *
 * @package Modules\RedmineIntegration\Console
 */
class SynchronizeTasks extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'redmine-synchronize:tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize tasks from redmine for all users, who activate redmine integration.';

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
     * @param TaskIntegrationHelper $taskIntegrationHelper
     * @param UserRepository $repo
     * @return mixed
     */
    public function handle(TaskIntegrationHelper $taskIntegrationHelper, UserRepository $repo)
    {
        $taskIntegrationHelper->synchronizeTasks($repo);
    }
}
