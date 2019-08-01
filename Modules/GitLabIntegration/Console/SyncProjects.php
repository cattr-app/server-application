<?php

namespace Modules\GitLabIntegration\Console;

use Illuminate\Console\Command;
use Modules\GitLabIntegration\Helpers\GitLabProjects;

class SyncProjects extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'gitlab-sync:projects';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize projects from the GitLab for all users, who activate the GitLab integration.';

    /**
     * @var GitLabProjects
     */
    protected $projects;

    /**
     * Create a new command instance.
     */
    public function __construct(GitLabProjects $projects)
    {
        parent::__construct();

        $this->projects = $projects;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->projects->syncAllProjects();
    }
}
