<?php

namespace App\Http\Controllers\Api\v1\Statistic;

use Auth;
use App\Models\ProjectsUsers;
use App\Models\Task;
use App\Models\TimeInterval;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\TimeDuration;
use Nwidart\Modules\Collection;
use App\User;
use DB;

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

        $projectReports = DB::table('project_report')
            ->select('user_id', 'user_name', 'task_id', 'project_id', 'task_name', 'project_name', DB::raw('SUM(duration) as duration'))
            ->whereIn('user_id', $uids)
            ->whereIn('project_id', $pids)
            ->whereIn('project_id', Project::getUserRelatedProjectIds(Auth::user()))
            ->where('date', '>=', $start_at)
            ->where('date', '<', $end_at)
            ->groupBy('user_id', 'user_name', 'task_id', 'project_id', 'task_name', 'project_name')
            ->get();

        $projects = [];


        foreach ($projectReports as $projectReport) {
            $project_id = $projectReport->project_id;
            $user_id = $projectReport->user_id;

            if (!isset($projects[$project_id])) {
                $projects[$project_id] = [
                    'id' => $project_id,
                    'name' => $projectReport->project_name,
                    'users' => [],
                    'project_time' => 0,
                ];
            }

            if (!isset($projects[$project_id]['users'][$user_id])) {
                $projects[$project_id]['users'][$user_id] = [
                    'id' => $user_id,
                    'full_name' => $projectReport->user_name,
                    'tasks' => [],
                    'tasks_time' => 0,
                ];
            }


            $projects[$project_id]['users'][$user_id]['tasks'][] = [
                'id' => $projectReport->task_id,
                'project_id' => $projectReport->project_id,
                'user_id' => $projectReport->user_id,
                'task_name' => $projectReport->task_name,
                'duration' => (int)$projectReport->duration,
            ];

            $projects[$project_id]['users'][$user_id]['tasks_time'] += $projectReport->duration;
            $projects[$project_id]['project_time'] += $projectReport->duration;
        }


        foreach ($projects as $project_id => $project) {
            $projects[$project_id]['users'] =  array_values($project['users']);
        }

        $projects = array_values($projects);

        return $projects;
    }

    /**
     * [events description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function days(Request $request)
    {
        $start_at = is_null($request->input('start_at')) ? '' : $request->start_at;
        $end_at = is_null($request->input('end_at')) ? '' : $request->end_at;
        $uids = $request->uids;

        $days = DB::table('project_report')
            ->select('user_id', 'date', DB::raw('SUM(duration) as duration'))
            ->whereIn('project_id', Project::getUserRelatedProjectIds(Auth::user()))
            ->groupBy('user_id', 'date')
        ;

        if ($start_at) {
            $days->where('date', '>=', $date);
        }

        if ($end_at) {
            $days->where('date', '<', $date);
        }

        if (!empty($uids)) {
            $days->whereIn('user_id', $uids);
        }

        return response()->json($days->get());
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
        $users_attached_project_ids = Project::whereHas('users', function ($query) use ($uids) {
            $query->whereIn('id', $uids);
        })->pluck('id');

        // Get projects, where specified users have intervals.
        $users_related_project_ids = Project::whereHas('tasks.timeIntervals', function ($query) use ($uids) {
            $query->whereIn('user_id', $uids);
        })->pluck('id');

        $project_ids = collect([$users_attached_project_ids, $users_related_project_ids])->collapse()->unique();

        // Get projects, directly attached to the current user.
        $attached_project_ids = Project::whereHas('users', function ($query) use ($uids) {
            $query->where('id', Auth::user()->id);
        })->pluck('id');

        // Filter projects by directly attached to the current user, if have attached.
        if ($attached_project_ids->count() > 0) {
            $project_ids = $project_ids->intersect($attached_project_ids);
        }

        // Load projects.
        $projects = Project::query()->whereIn('id', $project_ids)->get(['id', 'name']);

        return response()->json($projects);
    }

    /**
     * Returns durations per date for a task.
     */
    public function task($id, Request $request)
    {
        $uid = $request->uid;
        $start_at = $request->input('start_at') == null ? '' : $request->start_at;
        $end_at = $request->input('end_at') == null ? '' : $request->end_at;

        $report = DB::table('project_report')
            ->select('date', DB::raw('CAST(duration AS UNSIGNED) AS duration'))
            ->where('task_id', $id)
            ->where('user_id', $uid)
            ->where('date', '>=', $start_at)
            ->where('date', '<', $end_at)
            ->get(['date', 'duration']);

        return response()->json($report);
    }
}
