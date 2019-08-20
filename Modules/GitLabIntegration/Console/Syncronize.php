<?php

namespace Modules\GitlabIntegration\Console;

use Illuminate\Console\Command;
use Modules\GitLabIntegration\Helpers\GitLabProjects;
use Modules\GitlabIntegration\Helpers\Synchronizer;

class Syncronize extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'gitlab:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize projects from the Gitlab for all users, who activate the Gitlab integration.';

    protected $synchronizer;

    /**
     * Create a new command instance.
     */
    public function __construct(Synchronizer $synchronizer)
    {
        parent::__construct();

        $this->synchronizer = $synchronizer;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->synchronizer->synchronizeAll();
    }
}
