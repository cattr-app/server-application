<?php

namespace App\Http\Controllers\Api\v1\Statistic;

use App\Models\ProjectsUsers;
use App\Models\Task;
use App\Models\TimeInterval;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Nwidart\Modules\Collection;
use App\User;

class ProjectReportController extends Controller
{
  /**
   * Handle the incoming request.
   *
   * @param  \Illuminate\Http\Request $request
   * @return \Illuminate\Http\Response
   */
  public function index(Request $request)
  {
    $type = $request->input('type');
    $start_at = $request->input('start_at');
    $end_at = $request->input('end_at');
    return $this->$type($start_at, $end_at);
  }

  /**
   * [resources description]
   * @param  Request $request [description]
   * @return [type]           [description]
   */
  public function resources($uids, $pids, $start_at, $end_at)
  {
    $projects = array_map(function ($project_id) use ($uids, $start_at, $end_at) {
      $project = Project::where('id', $project_id)->select('id', 'name')->first();

      // Load users with data.
      $users = User::whereIn('id', $uids)
        ->with(['tasks' => function ($query) use ($project_id, $start_at, $end_at) {
          $query
            ->with(['timeIntervals' => function ($query) use ($start_at, $end_at) {
              $query->where([['start_at', '>', $start_at], ['end_at', '<', $end_at]])
                ->select('task_id')
                ->selectRaw('CONVERT(SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)), SIGNED INTEGER) duration')
                ->groupBy('task_id');
            }])
            ->where('project_id', $project_id)
            ->select('id', 'project_id', 'user_id', 'task_name');
        }])
        ->get(['id', 'full_name', 'avatar']);

      // Process data.
      $users = array_map(function ($user) {
        $tasks = array_map(function ($task) {
          $task_data = (array)$task;
          $task_data['duration'] = !empty($task_data['time_intervals']) ? $task_data['time_intervals'][0]['duration'] : 0;
          unset($task_data['time_intervals']);
          return $task_data;
        }, $user['tasks']);

        $tasks = array_filter($tasks, function ($task) {
          return $task['duration'] > 0;
        });

        $tasks_time = array_reduce($tasks, function ($sum, $task) {
          return $sum + $task['duration'];
        }, 0);

        $user_data = (array)$user;
        $user_data['tasks'] = array_values($tasks);
        $user_data['tasks_time'] = $tasks_time;
        return $user_data;
      }, $users->toArray());

      $users = array_filter($users, function ($user) {
        return $user['tasks_time'] > 0;
      });

      $project_time = array_reduce($users, function ($sum, $user) {
        return $sum + $user['tasks_time'];
      }, 0);

      $project_data = [
        'id' => $project['id'],
        'name' => $project['name'],
        'users' => array_values($users),
        'project_time' => $project_time,
      ];
      return $project_data;
    }, $pids);

    $projects = array_filter($projects, function ($project) {
      return $project['project_time'] > 0;
    });

    return array_values($projects);
  }

  /**
   * [events description]
   * @param  Request $request [description]
   * @return [type]           [description]
   */
  public function report(Request $request)
  {
    $start_at = $request->input('start_at') == null ? '' : $request->start_at;
    $end_at = $request->input('end_at') == null ? '' : $request->end_at;
    $uids = $request->uids;
    $pids = $request->pids;
    return response()->json($this->resources($uids, $pids, $start_at, $end_at));
  }

  public function projects(Request $request)
  {
    $uids = $request->uids;
    // Get projects, where specified users is attached.
    $attached_project_ids = Project::whereHas('users', function ($query) use ($uids) {
      $query->whereIn('id', $uids);
    })->pluck('id');

    // Get projects, where specified users have intervals.
    $related_project_ids = Project::whereHas('tasks.timeIntervals', function ($query) use ($uids) {
      $query->whereIn('user_id', $uids);
    })->pluck('id');

    // Load projects.
    $project_ids = collect([$attached_project_ids, $related_project_ids])->collapse()->unique();
    $projects = Project::query()->whereIn('id', $project_ids)->get(['id', 'name']);

    return response()->json($projects);
  }
}
