<?php

namespace Modules\RedmineIntegration\Console;

use App\Models\Priority;
use App\Models\Property;
use App\Models\Task;
use App\User;
use Illuminate\Console\Command;
use Log;
use Modules\RedmineIntegration\Entities\Repositories\ProjectRepository;
use Modules\RedmineIntegration\Entities\Repositories\TaskRepository;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Models\RedmineClient;
use Redmine;

/**
 * Class SynchronizePriorities
 *
 * @package Modules\RedmineIntegration\Console
 */
class SynchronizePriorities extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'redmine-synchronize:priorities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize priorities from redmine for all users, who activated redmine integration.';

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
        $this->synchronizePriorities();
    }

    /**
     * Synchronize priorities for all users
     */
    public function synchronizePriorities()
    {
        $users = User::all();

        foreach ($users as $user) {
            try {
                $this->synchronizeUserPriorities($user->id);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Synchronize priorities for current user
     *
     * @param int $userId User's id in our system
     *
     */
    public function synchronizeUserPriorities(int $userId)
    {
        $client = $this->initRedmineClient($userId);
        // Merge priorities info from the redmine with the stored internal priority_id.
        $available_priorities = $client->issue_priority->all()['issue_priorities'];
        $saved_priorities = $this->userRepo->getUserRedminePriorities($userId);
        $priorities = array_map(function ($priority) use ($saved_priorities) {
            $saved_priority = array_first($saved_priorities, function ($saved_priority) use ($priority) {
                return $saved_priority['id'] === $priority['id'];
            });

            if (isset($saved_priority) && Priority::find($saved_priority['priority_id'])) {
                $priority['priority_id'] = $saved_priority['priority_id'];
            } elseif (Priority::find($priority['id'])) {
                $priority['priority_id'] = $priority['id'];
            } else {
                $priority['priority_id'] = Priority::max('id');
            }

            return $priority;
        }, $available_priorities);
        $property = Property::updateOrCreate([
            'entity_id' => $userId,
            'entity_type' => Property::USER_CODE,
            'name' => 'REDMINE_PRIORITIES',
        ], [
            'value' => serialize($priorities),
        ]);
    }

    public function initRedmineClient(int $userId): Redmine\Client
    {
        $client = new RedmineClient($userId);

        return $client;
    }
}
