<?php

namespace App\Http\Controllers\Api\v1\Statistic;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\User;
use Auth;
use Illuminate\Http\Request;
use DB;

/**
 * Class TimeUseReportController
 * @package App\Http\Controllers\Api\v1\Statistic
 */
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

        $projectReports = DB::table('project_report')
            ->select('user_id', 'user_name', 'task_id', 'project_id', 'task_name', 'project_name', DB::raw('SUM(duration) as duration'))
            ->whereIn('user_id', $user_ids)
            ->whereIn('project_id', Project::getUserRelatedProjectIds(Auth::user()))
            ->where('date', '>=', $start_at)
            ->where('date', '<', $end_at)
            ->groupBy('user_id', 'user_name', 'task_id', 'project_id', 'task_name', 'project_name')
            ->get();

        $users = [];

        foreach ($projectReports as $projectReport) {
            $project_id = $projectReport->project_id;
            $user_id = $projectReport->user_id;
            $duration = (int)$projectReport->duration;

            if (!isset($users[$user_id])) {
                $users[$user_id] = [
                    'user_id' => $user_id,
                    'name' => $projectReport->user_name,
                    'tasks' => [],
                    'total_time' => 0,
                ];
            }

            $users[$user_id]['tasks'][] = [
                'task_id' => $projectReport->task_id,
                'project_id' => $projectReport->project_id,
                'name' => $projectReport->task_name,
                'project_name' => $projectReport->project_name,
                'total_time' => $duration,
            ];

            $users[$user_id]['total_time'] += $duration;
        }

        $ret = [ ['users' => array_values($users) ] ];


        return response()->json($ret);
    }
}
