<?php

namespace Modules\RedmineIntegration\Console;

use App\Models\User;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\RedmineIntegration\Entities\ClientFactoryException;
use Modules\RedmineIntegration\Entities\Repositories\ProjectRepository;
use Modules\RedmineIntegration\Entities\Repositories\TaskRepository;
use Modules\RedmineIntegration\Entities\Repositories\TimeIntervalRepository;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Models\ClientFactory;
use Modules\RedmineIntegration\Models\Settings;

class SynchronizeTime extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'redmine:time';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize tracked time with redmine.';

    /**
     * @var UserRepository
     */
    protected $userRepo;

    /**
     * @var TimeIntervalRepository
     */
    protected $timeRepo;

    /**
     * @var ProjectRepository
     */
    protected $projectRepo;

    /**
     * @var TaskRepository
     */
    protected $taskRepo;

    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * @var Settings
     */
    protected $settings;

    public function __construct(
        UserRepository $userRepo,
        TaskRepository $taskRepo,
        TimeIntervalRepository $timeRepo,
        ClientFactory $clientFactory,
        Settings $settings
    ) {
        parent::__construct();

        $this->userRepo = $userRepo;
        $this->timeRepo = $timeRepo;
        $this->taskRepo = $taskRepo;
        $this->clientFactory = $clientFactory;
        $this->settings = $settings;
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->synchronizeTime();
    }

    /**
     * Synchronize time for selected users
     */
    public function synchronizeTime(): void
    {
        if (!$this->settings->getSendTime()) {
            return;
        }

        $users = User::all();
        foreach ($users as $user) {
            $intervalQuery = $this->timeRepo->getNotSyncedIntervals($user->id);
            $dates = [];

            $intervalQuery->orderBy('start_at');

            $intervalQuery->chunk(100, function ($intervals) use (&$dates) {
                foreach ($intervals as $interval) {
                    $start_timestamp = strtotime($interval->start_at);
                    $end_timestamp = strtotime($interval->end_at);
                    $date = date("Y-m-d", $start_timestamp);
                    $task_id = $interval->task_id;
                    $interval_id = $interval->id;
                    $hoursDiff = ($end_timestamp - $start_timestamp) / 3600.0;
                    $issue_id = $this->taskRepo->getRedmineTaskId($task_id);


                    if ($issue_id === null) {
                        continue; // skip non-redmine tasks
                    }


                    if (!isset($dates[$date])) {
                        $dates[$date] = [
                            $issue_id => [
                                'hours' => $hoursDiff,
                                'intervals' => [$interval_id],
                            ]
                        ];
                    } elseif (!isset($dates[$date][$issue_id])) {
                        $dates[$date][$issue_id] = [
                            'hours' => $hoursDiff,
                            'intervals' => [$interval_id],
                        ];
                    } else {
                        $dates[$date][$issue_id]['hours'] += $hoursDiff;
                        $dates[$date][$issue_id]['intervals'][] = $interval_id;
                    }
                }
            });

            foreach ($dates as $date => $issues) {
                foreach ($issues as $issue_id => $issue) {
                    $this->sendTime($user, $date, $issue_id, $issue['hours'], $issue['intervals']);
                }
            }
        }
    }


    protected function sendTime($user, $date, $issue_id, $hours, $timeIntervalIds): void
    {
        try {
            $client = $this->clientFactory->createUserClient($user->id);

            $ret = $client->time_entry->create([
                'issue_id' => $issue_id,
                'spent_on' => $date,
                'hours' => $hours,
                'comments' => 'Uploaded from Cattr',
            ]);

            $issent = isset($ret->id);

            if ($issent) {
                foreach ($timeIntervalIds as $timeIntervalId) {
                    $this->timeRepo->markAsSynced($timeIntervalId);
                }
            }
        } catch (ClientFactoryException $e) {
            Log::info($e->getMessage());
        }
    }
}
