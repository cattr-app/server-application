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
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $filter = $request->all() ?: [];
        $filter['user_id'] = $user->id;
        $filter['active'] = 1;

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $this->applyQueryFilter($this->getQuery(), $filter)
        );


        return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.item.list.result'),
                $itemsQuery->get()
            )
        );
    }

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

    public function dashboard(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $limit = request()->limit;
        $items = Task::where('user_id', '=', $user->id)
            ->whereHas('timeIntervals', function ($query) {

                    $YersterdayTimestamp = time() - 60 /* sec */ * 60  /* min */ * 24 /* hours */;
                    $compareDate = date("Y-m-d H:i:s", $YersterdayTimestamp );

                    $query->where('updated_at', '>=', $compareDate);
            })
            ->take(10)
            ->get();

        foreach ($items as $key => $task) {
            $totalTime = 0;

            foreach ($task->timeIntervals as $timeInterval) {
                $end = new DateTime($timeInterval->end_at);
                $totalTime += $end->diff(new DateTime($timeInterval->start_at))->s;
            }

            $items[$key]->total_time = gmdate("H:i:s", $totalTime);
        }


        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.list'), $items),
            200
        );
    }

}
