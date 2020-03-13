<?php


namespace Modules\GitlabIntegration\Helpers;


use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TimeIntervalsHelper
{
    const GIS_TABLE = 'gitlab_intervals_sync';
    const GTR_TABLE = 'gitlab_tasks_relations';
    const GPR_TABLE = 'gitlab_projects_relations';

    public function createUnsyncedInterval(TimeInterval $interval) : bool
    {
        $task = $this->getTasksById($interval->task_id);
        if ($task && $task->task_id) {
            return DB::table(self::GIS_TABLE)->insert([
                'task_id' => $interval->task_id,
                'time_interval_id' => $interval->id,
                'is_synced' => 0
            ]);
        }
        return false;
    }

    public function markAsSyncedIntervalByTaskId(int $taskId) : int
    {
        return DB::table(self::GIS_TABLE)->where('task_id', '=', $taskId)->update([
            'is_synced' => 1
        ]);
    }

    public function clearSyncedIntervals() : int
    {
        return DB::table(self::GIS_TABLE)
            ->where('is_synced', '=', true)
            ->delete();
    }

    public function getNotSyncedCollection() : Collection
    {
        return DB::table(self::GIS_TABLE)
            ->where('is_synced', '=', false)
            ->orWhereNull('is_synced')
            ->get();
    }

    public function getTasksById(int $taskId)
    {
        return DB::table(self::GTR_TABLE)
            ->where('task_id', '=', $taskId)
            ->first();
    }

    public function getProjectRelation(int $projectId)
    {
        return DB::table(self::GPR_TABLE)
            ->where('project_id', '=' ,$projectId)
            ->first();
    }

    public function getGitlabIssueProjectRelation(Collection $tasks) : array
    {
        $projectIds = $tasks->groupBy('project_id')->keys();

        $result = [];
        foreach ($projectIds as $projectId) {
            foreach ($tasks as $task) {
                if (!isset($result[$task->id])) {
                    $projectRelation = $this->getProjectRelation($projectId);
                    $taskRelation = $this->getTasksById($task->id);

                    $result[$task->id] = [
                        'gl_project_id' => $projectRelation->gitlab_id,
                        'gl_issue_iid' => $taskRelation->gitlab_issue_iid,
                    ];
                }
            }
        }
        return $result;
    }
}
