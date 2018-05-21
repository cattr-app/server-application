<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Task;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Filter;
use DateTime;



/**
 * Class TaskController
 *
 * @package App\Http\Controllers\Api\v1
 */
class TaskController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return Task::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'project_id'  => 'required',
            'task_name'   => 'required',
            'active'      => 'required',
            'user_id'     => 'required',
            'assigned_by' => 'required',
            'url'         => 'required'
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'task';
    }

    /**
     * @return string[]
     */
    public function getQueryWith(): array
    {
        return [
            'user', 'project', 'assigned',
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @api {post} /api/v1/tasks/list List
     * @apiDescription Get list of Tasks
     * @apiVersion 0.1.0
     * @apiName GetTaskList
     * @apiGroup Task
     *
     * @apiParam {Integer} [id] `QueryParam` Task ID
     * @apiParam {Integer} [project_id] `QueryParam` Task Project
     * @apiParam {String} [task_name] `QueryParam` Task Name
     * @apiParam {Boolean} [active] Active/Inactive Task
     * @apiParam {Integer} [user_id] `QueryParam` Task's User
     * @apiParam {Integer} [assigned_by] `QueryParam` User who assigned task
     * @apiParam {DateTime} [created_at] `QueryParam` Task Creation DateTime
     * @apiParam {DateTime} [updated_at] `QueryParam` Last Task update DataTime
     * @apiParam {DateTime} [deleted_at] `QueryParam` When Task was deleted (null if not)
     *
     * @apiSuccess (200) {Task[]} TaskList array of Task objects
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * @api {post} /api/v1/tasks/create Create
     * @apiDescription Create Task
     * @apiVersion 0.1.0
     * @apiName CreateTask
     * @apiGroup Task
     */

    /**
     * @api {post} /api/v1/tasks/show Show
     * @apiDescription Show Task
     * @apiVersion 0.1.0
     * @apiName ShowTask
     * @apiGroup Task
     */

    /**
     * @api {post} /api/v1/tasks/edit Edit
     * @apiDescription Edit Task
     * @apiVersion 0.1.0
     * @apiName EditTask
     * @apiGroup Task
     */

    /**
     * @api {post} /api/v1/tasks/destroy Destroy
     * @apiDescription Destroy Task
     * @apiVersion 0.1.0
     * @apiName DestroyTask
     * @apiGroup Task
     */

    /**
     * @api {post} /api/v1/tasks/dashboard Dashboard
     * @apiDescription Display task for dashboard
     * @apiVersion 0.1.0
     * @apiName DashboardTask
     * @apiGroup Task
     * @apiParam {Integer} [id] `QueryParam` Task ID
     * @apiParam {Integer} [project_id] `QueryParam` Task Project
     * @apiParam {String} [task_name] `QueryParam` Task Name
     * @apiParam {Boolean} [active] Active/Inactive Task
     * @apiParam {Integer} [user_id] `QueryParam` Task's User ID and Time Interval's User ID
     * @apiParam {Integer} [assigned_by] `QueryParam` User who assigned task
     * @apiParam {DateTime} [created_at] `QueryParam` Task Creation DateTime
     * @apiParam {DateTime} [updated_at] `QueryParam` Last Task update DataTime
     * @apiParam {DateTime} [deleted_at] `QueryParam` When Task was deleted (null if not)
     *
     * @apiSuccess (200) {Task[TimeInterval]} TaskList array of Task with Time Interval objects
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(Request $request): JsonResponse
    {
        $filters = $request->all();
        $YersterdayTimestamp = time() - 60 /* sec */ * 60  /* min */ * 24 /* hours */;
        $request->get('user_id') ? $filters['timeIntervals.user_id'] = (int) $request->get('user_id') : False;
        $compareDate = date("Y-m-d H:i:s", $YersterdayTimestamp );
        $filters['timeIntervals.update_at'] = ['>=', $compareDate];
        unset($filters['user_id']);

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery(False),
            $filters ?: []
        );

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $baseQuery
        );

        $items = $itemsQuery->with(['TimeIntervals' => function($q) use ($request) {
            $request->get('user_id') ? $q->where('user_id', '=', $request->get('user_id')) : False;
        }])->get()->toArray();

        if (collect($items)->isEmpty()) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.success.item.list'),
                []
            ));
        }

        foreach ($items as $key => $task) {
            $totalTime = 0;

            foreach ($task['time_intervals'] as $timeInterval) {
                $end = new DateTime($timeInterval['end_at']);
                $totalTime += $end->diff(new DateTime($timeInterval['start_at']))->s;
            }

            $items[$key]['total_time'] = gmdate("H:i:s", $totalTime);
        }


        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.list'), $items),
            200
        );
    }

}
