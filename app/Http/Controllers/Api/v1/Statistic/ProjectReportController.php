<?php

namespace App\Http\Controllers\Api\v1\Statistic;

use App\Models\ProjectsUsers;
use DB;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\v1\TimeIntervalController;
use App\Models\TimeInterval;
use App\Models\Task;
use App\Models\Project;
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
  public function resources($start_at, $end_at, $uids, $pids)
  {
    $projects = Project::with(['users' => function ($q) use ($start_at, $end_at, $uids) {
      return $q->with(['tasks' => function ($q) use ($start_at, $end_at) {
        return $q->with(['timeIntervals' => function ($q) use ($start_at, $end_at) {
          return $q->where([['start_at', '>', $start_at], ['end_at', '<', $end_at]])
            ->select('task_id')
            ->selectRaw('SUM(TIMESTAMPDIFF(SECOND, start_at, end_at)) duration')
            ->groupBy('task_id');
        }])->select('id', 'project_id', 'user_id', 'task_name');
      }])->select('full_name', 'id')->whereIn('id', $uids);
    }])->whereHas('users', function ($q) use ($start_at, $end_at, $uids) {
      return $q->whereHas('tasks', function ($q) use ($start_at, $end_at) {
        return $q->whereHas('timeIntervals', function ($q) use ($start_at, $end_at) {
          return $q->where([['start_at', '>', $start_at], ['end_at', '<', $end_at]]);
        });
      })->whereIn('id', $uids);
    })->select('id', 'name')
      ->whereIn('id', $pids)
      ->get();
    $projects = $projects->map(function ($project) {
      $project_time = 0;
      $project->users = $project->users->map(function ($user) use (&$project_time) {
        $tasks_time = 0;
        $user->tasks->each(function ($task) use (&$tasks_time) {
          $tasks_time += $task->timeIntervals[0]->duration;
        });
        $user['tasks_time'] = $tasks_time;
        $project_time += $tasks_time;
        return $user;
      });
      $project['project_time'] = $project_time;
      return $project;
    });
//    dd($projects[0]->users[0]->full_name);
//    dd($projects->toArray());
    return response()->json($projects);
  }

  /**
   * [events description]
   * @param  Request $request [description]
   * @return [type]           [description]
   */
  public function events(Request $request)
  {
    $start_at = $request->input('start_at');
    $end_at = $request->input('end_at');
    return response()->json($this->resources($start_at, $end_at, [1, 2], [1, 2]));


    $timeIntervals = TimeInterval::with('task:id,project_id')->where([
      ['start_at', '>', $start_at],
      ['end_at', '<', $end_at]
    ])->get();
    $events = $timeIntervals->map(function ($item) {
      return [
        'id' => $item->id,
        'title' => '',
        'resourceId' => [$item->task->project_id, $item->user_id],
        'start' => (new \DateTime($item->start_at))->format('c'),
        'end' => (new \DateTime($item->end_at))->format('c')
      ];
    });

    return response()->json($events);
  }

  public function worked(Request $request)
  {
    return response()->json('piu');
  }
}
