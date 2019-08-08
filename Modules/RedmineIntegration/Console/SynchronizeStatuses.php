<?php

namespace Modules\RedmineIntegration\Console;

use App\Models\Property;
use App\User;
use Exception;
use Illuminate\Console\Command;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Models\RedmineClient;
use Redmine;

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
    protected $description = 'Synchronize statuses from redmine for all users, who activated redmine integration.';

    /**
     * @var UserRepository
     */
    protected $userRepo;

    /**
     * Create a new command instance.
     *
     * @param  UserRepository  $userRepo
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
        $this->synchronizeStatuses();
    }

    /**
     * Synchronize statuses for all users
     */
    public function synchronizeStatuses()
    {
        $users = User::all();

        foreach ($users as $user) {
            try {
                $this->synchronizeUserStatuses($user->id);
            } catch (Exception $e) {
            }
        }
    }

    /**
     * Synchronize statuses for current user
     *
     * @param  int  $userId  User's id in our system
     *
     */
    public function synchronizeUserStatuses(int $userId)
    {
        $client = $this->initRedmineClient($userId);
        // Merge statuses info from the redmine with the stored 'is_active' property.
        $available_statuses = $client->issue_status->all()['issue_statuses'];
        $saved_statuses = $this->userRepo->getUserRedmineStatuses($userId);

        $statuses = array_map(function ($status) use ($saved_statuses) {
            $saved_status = array_first($saved_statuses, function ($saved_status) use ($status) {
                return $saved_status['id'] === $status['id'];
            });
            $status['is_active'] = isset($saved_status)
                ? $saved_status['is_active']
                : !isset($status['is_closed']);
            return $status;
        }, $available_statuses);

        $property = Property::updateOrCreate([
            'entity_id' => $userId,
            'entity_type' => Property::USER_CODE,
            'name' => 'REDMINE_STATUSES',
        ], [
            'value' => serialize($statuses),
        ]);
    }

    public function initRedmineClient(int $userId): Redmine\Client
    {
        $client = new RedmineClient($userId);

        return $client;
    }
}
