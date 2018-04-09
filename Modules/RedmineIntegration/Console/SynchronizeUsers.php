<?php

namespace Modules\RedmineIntegration\Console;

use Illuminate\Console\Command;
use Log;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Models\RedmineClient;
use Redmine;

/**
 * Class SynchronizeProjects
 *
 * @package Modules\RedmineIntegration\Console
 */
class SynchronizeUsers extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'redmine-synchronize:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize users with redmine';

    /**
     * @var UserRepository
     */
    protected $userRepo;

    /**
     * Create a new command instance.
     *
     * @param UserRepository $userRepo
     */
    public function __construct(UserRepository $userRepo)
    {
        parent::__construct();

        $this->userRepo = $userRepo;
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->synchronizeNewUsers();
    }

    /**
     * Synchronize users, who activated redmine integration with redmine users
     *
     * Add row with user's redmine id to properties table
     */
    public function synchronizeNewUsers()
    {
        $newRedmineUsers = $this->userRepo->getNewRedmineUsers();

        foreach ($newRedmineUsers as $newRedmineUser) {
            $client = $this->initRedmineClient($newRedmineUser->entity_id);
            $currentUserInfo = $client->user->getCurrentUser();

            $this->userRepo->setRedmineId($newRedmineUser->entity_id, $currentUserInfo['user']['id']);
            $this->userRepo->markAsOld($newRedmineUser->entity_id);
        }
    }

    public function initRedmineClient(int $userId): Redmine\Client
    {
        $client = new RedmineClient($userId);

        return $client;
    }
}
