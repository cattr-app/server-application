<?php

namespace App\Http\Controllers\Api\v1\Statistic;

use Auth;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\User;
use Illuminate\Http\Request;
use DB;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function timeIntervals(Request $request)
    {
        $user_ids = $request->input('user_ids');
        $start_at = $request->input('start_at');
        $end_at = $request->input('end_at');

        $intervals = DB::table('time_intervals AS i')->leftJoin('tasks AS t', 'i.task_id', '=', 't.id')
            ->select('i.user_id', 'i.id', 'i.task_id', 't.project_id', 'i.start_at', 'i.end_at',
                DB::raw('1000 * TIMESTAMPDIFF(SECOND, i.start_at, i.end_at) as duration'))
            ->whereIn('i.user_id', $user_ids)
            ->where('i.start_at', '>=', $start_at)
            ->where('i.start_at', '<', $end_at)
            ->whereIn('t.project_id', Project::getUserRelatedProjectIds(Auth::user()))
            ->whereNull('i.deleted_at')
            ->orderBy('i.start_at')
            ->get();

        $users = [];
        foreach ($intervals as $interval) {
            $user_id = (int)$interval->user_id;
            $duration = (int)$interval->duration;

            if (!isset($users[$user_id])) {
                $users[$user_id] = [
                    'user_id' => $user_id,
                    'intervals' => [],
                    'duration' => 0,
                ];
            }

            $users[$user_id]['intervals'][] = [
                'id' => (int)$interval->id,
                'user_id' => (int)$user_id,
                'task_id' => (int)$interval->task_id,
                'project_id' => (int)$interval->project_id,
                'duration' => $duration,
                'start_at' => $interval->start_at,
                'end_at' => $interval->end_at,
            ];

            $users[$user_id]['duration'] += $duration;
        }

        $results = ['userIntervals' => $users];
        return response()->json($results);
    }
}
