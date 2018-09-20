<?php

namespace App\Http\Controllers\Api\v1\Statistic;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;

class TimeUseReportController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function report(Request $request)
    {
        $user_ids = $request->input('user_ids');
        $start_at = $request->input('start_at');
        $end_at = $request->input('end_at');

        // Load data.
        $users = User::whereIn('users.id', $user_ids)
            ->with(['tasks' => function ($query) use ($start_at, $end_at) {
                $query
                    ->with(['timeIntervals' => function ($query) use ($start_at, $end_at) {
                        $query
                            ->where([['start_at', '>', $start_at], ['end_at', '<', $end_at]])
                            ->select('task_id')
                            ->selectRaw('CONVERT(SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)), SIGNED INTEGER) total_time')
                            ->groupBy('task_id');
                    }])
                    ->with(['project' => function ($query) {
                        $query->select('id', 'name');
                    }])
                    ->select('id', 'project_id', 'user_id', 'task_name');
            }])
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'avatar'])
            ->toArray();

        // Process data.
        $users = array_map(function ($user) {
            $tasks = array_map(function ($task) {
                $total_time = !empty($task['time_intervals'])
                    ? $task['time_intervals'][0]['total_time']
                    : 0;

                $project_name = isset($task['project'])
                    ? $task['project']['name']
                    : '';

                return [
                    'task_id' => $task['id'],
                    'project_id' => $task['project_id'],
                    'name' => $task['task_name'],
                    'project_name' => $project_name,
                    'total_time' => $total_time,
                ];
            }, $user['tasks']);

            // Exclude tasks with zero total time.
            $tasks = array_values(array_filter($tasks, function ($task) {
                return $task['total_time'] > 0;
            }));

            // Sort by total time.
            usort($tasks, function ($a, $b) {
                return $b['total_time'] - $a['total_time'];
            });

            // Calculate total time by all tasks for an user.
            $total_time = array_reduce(array_map(function ($task) {
                return $task['total_time'];
            }, $tasks), function ($total, $time) {
                return $total + $time;
            }, 0);

            return [
                'user_id' => $user['id'],
                'name' => $user['full_name'],
                'avatar' => $user['avatar'],
                'tasks' => $tasks,
                'total_time' => $total_time,
            ];
        }, $users);

        // Exclude users with zero total time.
        $users = array_values(array_filter($users, function ($user) {
            return $user['total_time'] > 0;
        }));

        // Sort by user name.
        usort($users, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        $data = [];
        $data['users'] = $users;

        return response()->json([$data]);
    }
}
