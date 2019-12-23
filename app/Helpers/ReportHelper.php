<?php

namespace App\Helpers;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

class ReportHelper
{

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
     * @param          $timezoneOffset
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

        return DB::table('projects')
            ->selectRaw($select, [$timezoneOffset])
            ->join('tasks', 'tasks.project_id', '=', 'projects.id')
            ->join('time_intervals', function ($join) use ($startAt, $endAt) {
                $join
                    ->on('time_intervals.task_id', '=', 'tasks.id');
                /*->where('time_intervals.start_at', '>=', $startAt)
                ->where('time_intervals.end_at', '<', $endAt);*/
            })
            ->join('users', function ($join) use ($uids) {
                $join
                    ->on('time_intervals.user_id', '=', 'users.id');
                //->whereIn('users.id', $uids);
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
}
