<?php

namespace App\Helpers;

use App\Models\Project;
use App\Models\Screenshot;
use App\Models\Task;
use App\Models\TimeInterval;
use App\User;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class ReportHelper
{
    /**
     * @var Project
     */
    protected $project;

    /**
     * @var TimeInterval
     */
    protected $timeInterval;

    /**
     * @var User
     */
    protected $user;
    /**
     * @var Task
     */
    protected $task;
    /**
     * @var Screenshot
     */
    protected $screenshot;

    /**
     * ReportHelper constructor.
     *
     * @param  Project       $project
     * @param  TimeInterval  $timeInterval
     * @param  User          $user
     * @param  Task          $task
     * @param  Screenshot    $screenshot
     */
    public function __construct(
        Project $project,
        TimeInterval $timeInterval,
        User $user,
        Task $task,
        Screenshot $screenshot
    ) {
        $this->project = $project;
        $this->timeInterval = $timeInterval;
        $this->user = $user;
        $this->task = $task;
        $this->screenshot = $screenshot;
    }

    /**
     * @param $collection
     *
     * @return Collection
     */
    public function getProcessedCollection($collection): Collection
    {
        $collection = $collection->groupBy('project_name');

        $resultCollection = [];
        foreach ($collection as $projectName => $items) {
            foreach ($items as $item) {
                if (!array_key_exists($projectName, $resultCollection)) {
                    $resultCollection[$projectName] = [
                        'id' => $item->project_id,
                        'name' => $item->project_name,
                        'project_time' => 0,
                        'users' => [],
                    ];
                }
                if (!array_key_exists($item->user_id, $resultCollection[$projectName]['users'])) {
                    $resultCollection[$projectName]['users'][$item->user_id] = [
                        'id' => $item->user_id,
                        'full_name' => $item->user_name,
                        'tasks' => [],
                        'tasks_time' => 0,
                    ];
                }
                if (!array_key_exists($item->task_id,
                    $resultCollection[$projectName]['users'][$item->user_id]['tasks'])) {

                    $resultCollection[$projectName]['users'][$item->user_id]['tasks'][$item->task_id] = [
                        'task_name' => $item->task_name,
                        'id' => $item->task_id,
                        'duration' => 0,
                        'screenshots' => [],
                    ];
                }

                $resultCollection[$projectName]['users'][$item->user_id]['tasks'][$item->task_id]['duration'] +=
                    $item->task_duration;

                $screenshotsCollection = collect(json_decode($item->screens, true))
                    ->groupBy(function ($screen) {
                        return Carbon::parse($screen['created_at'])->format('Y-m-d');
                    })
                    ->transform(function ($screen) {
                        return $screen->groupBy(function ($screen) {
                            return Carbon::parse($screen['created_at'])->startOfHour()->format('H:i');
                        });
                    });

                $resultCollection[$projectName]['users'][$item->user_id]['tasks'][$item->task_id]['screenshots'] =
                    $screenshotsCollection;

            }
        }

        foreach ($resultCollection as &$project) {
            foreach ($project['users'] as &$user) {
                usort($user['tasks'], function ($a, $b) {
                    return $a['duration'] < $b['duration'];
                });

                foreach ($user['tasks'] as $task) {
                    $user['tasks_time'] += $task['duration'];
                }

                $project['project_time'] += $user['tasks_time'];
            }

            usort($project['users'], function ($a, $b) {
                return $a['tasks_time'] < $b['tasks_time'];
            });
        }

        usort($resultCollection, function ($a, $b) {
            return $a['project_time'] < $b['project_time'];
        });

        return collect($resultCollection);
    }

    /**
     * @param  array   $uids
     * @param  array   $pids
     * @param  string  $startAt
     * @param  string  $endAt
     * @param  mixed   $timezoneOffset
     *
     * @return Builder
     */
    public function getReportQuery(array $uids, array $pids, string $startAt, string $endAt, $timezoneOffset): Builder
    {
        $select = implode(', ', [
                'projects.id as project_id',
                'projects.name as project_name',
                'tasks.id as task_id',
                'tasks.task_name as task_name',
                'users.id as user_id',
                'users.full_name as user_name',
                'SUM(TIME_TO_SEC(TIMEDIFF(time_intervals.end_at, time_intervals.start_at))) as task_duration',
                "DATE_FORMAT(CONVERT_TZ(time_intervals.start_at, '+00:00', ?), '%Y-%m-%d') as task_date",
                "JSON_ARRAYAGG(JSON_OBJECT('id', screenshots.id, 'path', screenshots.path, 'thumbnail_path', screenshots.thumbnail_path, 'created_at', screenshots.created_at)) as screens",
            ]
        );

        return DB::table($this->getTableName('project'))
            ->selectRaw($select, [$timezoneOffset])
            ->join($this->getTableName('task'),
                $this->getTableName('task', 'project_id'),
                '=',
                $this->getTableName('project', 'id'))
            ->join($this->getTableName('timeInterval'), function ($join) {
                $join->on(
                    $this->getTableName('timeInterval', 'task_id'),
                    '=',
                    $this->getTableName('task', 'id')
                );
            })
            ->join($this->getTableName('user'), function ($join) {
                $join->on(
                    $this->getTableName('timeInterval', 'user_id'),
                    '=',
                    $this->getTableName('user', 'id')
                );
            })
            ->join('screenshots', 'screenshots.time_interval_id', '=', 'time_intervals.id')
            ->whereIn('projects.id', $pids)
            ->where('time_intervals.start_at', '>=', $startAt)
            ->where('time_intervals.end_at', '<', $endAt)
            ->whereIn('users.id', $uids)
            ->groupBy(['task_id', 'task_date'])
            ->orderBy('task_date', 'ASC')
            ->orderBy(DB::raw('ANY_VALUE(screenshots.created_at)'), 'ASC');
    }

    /**
     * @param  string       $entity
     *
     * @param  string|null  $column
     *
     * @return string
     */
    protected function getTableName(string $entity, string $column = null): string
    {
        if (!property_exists(static::class, $entity)) {
            throw new \RuntimeException("$entity does not exists on ".static::class);
        }

        $tblName = $this->{$entity}->getTable();
        return $column ? $tblName.".$column" : $tblName;
    }
}
