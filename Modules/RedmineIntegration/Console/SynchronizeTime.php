<?php

namespace Modules\RedmineIntegration\Console;

use App\Models\Property;
use App\Models\Task;
use App\Models\TimeInterval;
use App\User;
use Illuminate\Console\Command;
use Log;
use Modules\RedmineIntegration\Entities\Repositories\ProjectRepository;
use Modules\RedmineIntegration\Entities\Repositories\TaskRepository;
use Modules\RedmineIntegration\Entities\Repositories\UserRepository;
use Modules\RedmineIntegration\Entities\Repositories\TimeIntervalRepository;
use Modules\RedmineIntegration\Models\RedmineClient;
use Redmine;

/**
 * Class SynchronizeTime
 *
 * @package Modules\RedmineIntegration\Console
 */
class SynchronizeTime extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'redmine-synchronize:time';

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
     * Create a new command instance.
     *
     * @param UserRepository $userRepo
     * @param ProjectRepository $projectRepo
     * @param TaskRepository $taskRepo
     */
    public function __construct(UserRepository $userRepo, TaskRepository $taskRepo, TimeIntervalRepository $timeRepo)
    {
        parent::__construct();

        $this->userRepo = $userRepo;
        $this->timeRepo = $timeRepo;
        $this->taskRepo = $taskRepo;
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->synchronizeTime();
    }

    /**
     * Synchronize time for selected users
     */
    public function synchronizeTime()
    {
        $users = User::all();

        $users = $this->userRepo->getSendTimeUsers();


        foreach ($users as $user) {

            $intervalQuery = $this->timeRepo->getNotSyncedInvervals($user->id);
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


                    if (is_null($issue_id)) {
                        continue; // skip non-redmine tasks
                    }


                    if (!isset($dates[$date])) {
                        $dates[$date] = [
                            $issue_id => [
                                'hours' => $hoursDiff,
                                'intervals' => [$interval_id],
                            ]
                        ];
                    } else {
                        if (!isset($dates[$date][$issue_id])) {
                            $dates[$date][$issue_id] = [
                                'hours' => $hoursDiff,
                                'intervals' => [$interval_id],
                            ];
                        } else {
                            $dates[$date][$issue_id]['hours'] += $hoursDiff;
                            $dates[$date][$issue_id]['intervals'][] = $interval_id;

                        }
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


    protected function sendTime($user, $date, $issue_id, $hours, $timeIntervalIds)
    {

        try {
            $client = $this->initRedmineClient($user->id);

            $ret = $client->time_entry->create([
                'issue_id'  => $issue_id,
                'spent_on'  => $date,
                'hours'     => $hours,
                'comments'  => 'Upload from Amazing Time',
            ]);

            $issent = isset($ret->id);

            if ($issent) {

                foreach ($timeIntervalIds as $timeIntervalId) {
                    $this->timeRepo->markAsSynced($timeIntervalId);
                }
            }

        } catch (\Exception $e) {}
    }


    public function initRedmineClient(int $userId): Redmine\Client
    {
        $client = new RedmineClient($userId);

        return $client;
    }
}
