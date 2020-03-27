<?php

namespace Modules\RedmineIntegration\Console;

use Illuminate\Console\Command;
use Log;
use Modules\RedmineIntegration\Entities\ClientFactoryException;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Models\ClientFactory;

class SynchronizeUsers extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'redmine:users';

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
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * Create a new command instance.
     */
    public function __construct(UserRepository $userRepo, ClientFactory $clientFactory)
    {
        parent::__construct();

        $this->userRepo = $userRepo;
        $this->clientFactory = $clientFactory;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {
            $this->synchronizeNewUsers();
        } catch (ClientFactoryException $e) {
            Log::info($e->getMessage());
        }
    }

    /**
     * Synchronize users, who activated redmine integration with redmine users
     *
     * Add row with user's redmine id to properties table
     * @throws ClientFactoryException
     */
    public function synchronizeNewUsers(): void
    {
        $newRedmineUsers = $this->userRepo->getNewRedmineUsers();

        foreach ($newRedmineUsers as $newRedmineUser) {
            $client = $this->clientFactory->createUserClient($newRedmineUser->entity_id);
            $currentUserInfo = $client->user->getCurrentUser();

            $this->userRepo->setRedmineId($newRedmineUser->entity_id, $currentUserInfo['user']['id']);
            $this->userRepo->markAsOld($newRedmineUser->entity_id);
        }
    }
}
