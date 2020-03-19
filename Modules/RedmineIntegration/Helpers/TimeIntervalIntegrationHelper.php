<?php

namespace Modules\RedmineIntegration\Helpers;

use App\Models\Task;
use App\Models\TimeInterval;
use DateTime;
use Exception;
use Modules\RedmineIntegration\Entities\Repositories\ProjectRepository;
use Modules\RedmineIntegration\Entities\Repositories\TaskRepository;

class TimeIntervalIntegrationHelper extends AbstractIntegrationHelper
{
    public function createInterval(
        int $userId,
        int $timeIntervalId,
        TaskRepository $taskRepository,
        ProjectRepository $projectRepository
    ): void {
        $client = $this->clientFactory->createUserClient($userId);

        $timeInterval = TimeInterval::where('id', '=', $timeIntervalId)->first();
        $task = Task::where('id', '=', $timeInterval->task_id)->first();

        //calculate count of hours
        $startDateTime = new DateTime($timeInterval->start_at);
        $endDateTime = new DateTime($timeInterval->end_at);

        $diff = $endDateTime->diff($startDateTime);
        $diffHours = ($diff->days * 24) + $diff->h + ($diff->i / 60) + ($diff->s / 3600);

        $timeIntervalInfo = [
            'issue_id' => $taskRepository->getRedmineTaskId($task->id),
            'project_id' => $projectRepository->getRedmineProjectId($task->project_id),
            'spent_on' => $startDateTime->format('Y-m-d'),
            'hours' => round($diffHours, 2),
            'activity_id' => null,
            'comments' => 'Cattr Entry',
        ];

        $client->time_entry->create($timeIntervalInfo);
    }
}
