<?php

namespace App\Http\Controllers\Api\v1\Statistic;

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
     * @param  \Illuminate\Http\Request  $request
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
    public function resources($start_at, $end_at)
    {
        // get intervals
        $timeIntervals = TimeInterval::with('task:id,project_id')->where([
            ['start_at', '>', $start_at],
            ['end_at',   '<', $end_at]
        ])->get();

        // get users
        $users_id = $timeIntervals->pluck('user_id')->unique();
        $users = User::whereIn('id', $users_id)->get()->mapToGroups(function ($user) {
            return [
                $user->id => [
                    'id'    => $user->id,
                    'title' => $user->full_name
                ]
            ];
        })->toArray();

        // get projects
        $projects_id = $timeIntervals->pluck('task.project_id')->unique();
        $projects = Project::whereIn('id', $projects_id)->get()->mapToGroups(function ($project) {
            return [ $project->id => $project->name ];
        });

        // resource skeleton
        $resources = $timeIntervals
            ->mapToGroups(function ($item) {
                return [ $item->task->project_id => $item->user_id ];
            })
            ->map(function ($item) {
                return [ 'children' => collect($item)->unique()->values() ];
            })
            ->toArray();

            // dd($resources[29]['children']);

        // handle resources at array level
        foreach ($resources as $project_id => &$project) {
            $project['id'] = $project_id;
            $project['title'] = $projects[$project_id][0];
            foreach ($project['children'] as $index => $user) {
                $project['children'][$index] = $users[$user][0];
            }
            // dd($project);
            // $project->id = $project_id;
            // $project->title = $projects[$project_id][0];

            // $children = $project->children->toArray();
            // foreach ($children as $index => $user) {
            //     $children = $users[$user][0];
            // }
            // $project->children = $children;
        }

        return response()->json($resources);
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
