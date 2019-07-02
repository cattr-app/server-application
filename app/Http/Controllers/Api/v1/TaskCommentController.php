<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use App\Models\Screenshot;
use App\Models\TaskComment;
use App\Rules\BetweenDate;
use App\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Filter;
use Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

/**
 * Class TaskCommentController
 *
 * @package App\Http\Controllers\Api\v1
 */
class TaskCommentController extends ItemController
{
    /**
     * @apiDefine WrongDateTimeFormatStartEndAt
     *
     * @apiError (Error 401) {String} Error Error
     *
     * @apiErrorExample {json} DateTime validation fail
     * {
     *   "error": "validation fail",
     *     "reason": {
     *     "start_at": [
     *       "The start at does not match the format Y-m-d\\TH:i:sP."
     *     ],
     *     "end_at": [
     *       "The end at does not match the format Y-m-d\\TH:i:sP."
     *     ]
     *   }
     * }
     */

    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return TaskComment::class;
    }


    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'task_id'  => 'required',
            'content'  => 'required',
        ];
    }

    /**
     * @api {post} /api/v1/task-comment/create Create
     * @apiDescription Create Task Comment
     * @apiVersion 0.1.0
     * @apiName CreateTaskComment
     * @apiGroup Task Comment
     *
     * @apiUse UnauthorizedError
     *
     * @apiRequestExample {json} Request Example
     * {
     *   "task_id": 1,
     *   "user_id": 1,
     *   "start_at": "2013-04-12T16:40:00-04:00",
     *   "end_at": "2013-04-12T16:40:00-04:00"
     * }
     *
     * @apiSuccessExample {json} Answer Example
     * {
     *   "comment": {
     *     "id": 2251,
     *     "task_id": 1,
     *     "start_at": "2013-04-12 20:40:00",
     *     "end_at": "2013-04-12 20:40:00",
     *     "created_at": "2018-10-01 03:20:59",
     *     "updated_at": "2018-10-01 03:20:59",
     *     "count_mouse": 0,
     *     "count_keyboard": 0,
     *     "user_id": 1
     *   }
     * }
     *
     * @apiParam {Integer}  task_id   Task id
     * @apiParam {String}   content  Comment content
     *
     * @apiUse WrongDateTimeFormatStartEndAt
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.create'), $request->all());

        $validator = Validator::make(
            $requestData,
            Filter::process($this->getEventUniqueName('validation.item.create'), $this->getValidationRules())
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.create'), [
                    'error' => 'Validation fail',
                    'reason' => $validator->errors()
                ]),
                400
            );
        }

        $user = Auth::user();
        $cls = $this->getItemClass();

        $item = new $cls;

        $item->fill($this->filterRequestData($requestData));
        $item->user_id = $user->id;
        $item = Filter::process($this->getEventUniqueName('item.create'), $item);
        $item->save();



        $full_access = $user->allowed('task-comment', 'full_access');

        if (!$full_access) {

            if ($item->task->user_id != $user->id) {
                return response()->json([
                    'error' => "Access denied to this task",
                    'reason' => 'action is not allowed'
                ], 403);
            }
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                'res' => $item,
            ])
        );
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'taskcomment';
    }

    /**
     * @api {post} /api/v1/task-comment/list List
     * @apiDescription Get list of Task Comments
     * @apiVersion 0.1.0
     * @apiName GetTaskCommentList
     * @apiGroup TaskComment
     *
     * @apiParam {Integer}  [id]         `QueryParam` Task Comment id
     * @apiParam {Integer}  [task_id]    `QueryParam` Task Comment Task id
     * @apiParam {Integer}  [user_id]    `QueryParam` Task Comment User id
     * @apiParam {String}   [start_at]   `QueryParam` Task Comment Start DataTime
     * @apiParam {String}   [end_at]     `QueryParam` Task Comment End DataTime
     * @apiParam {String}   [created_at] `QueryParam` Task Comment Creation DateTime
     * @apiParam {String}   [updated_at] `QueryParam` Last Task Comment data update DataTime
     * @apiParam {String}   [deleted_at] `QueryParam` When Task Comment was deleted (null if not)
     *
     * @apiSuccess (200) {Object[]} TaskCommentList Task Comment
     *
     * @apiSuccessExample {json} Answer Example:
     * {
     *      {
     *          "id":1,
     *          "task_id":1,
     *          "start_at":"2006-06-20 15:54:40",
     *          "end_at":"2006-06-20 15:59:38",
     *          "created_at":"2018-10-15 05:54:39",
     *          "updated_at":"2018-10-15 05:54:39",
     *          "deleted_at":null,
     *          "count_mouse":42,
     *          "count_keyboard":43,
     *          "user_id":1
     *      },
     *      ...
     * }
     *
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->all();
        $request->get('project_id') ? $filters['task.project_id'] = $request->get('project_id') : False;

        $baseQuery = $this->applyQueryFilter(
            $this->getQuery()->with('user'),
            $filters ?: []
        );

        $user = Auth::user();
        $full_access = $user->allowed('task-comment', 'full_access');

        if (!$full_access) {
            $baseQuery->whereHas('task', function ($taskQuery) use ($user) {
                $taskQuery->where(['user_id' => $user->id]);
            });
        }

        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $baseQuery
        );

        return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.item.list.result'),
                $itemsQuery->get()
            )
        );
    }

    /**
     * @api {post} /api/v1/task-comment/show Show
     * @apiDescription Show Task Comment
     * @apiVersion 0.1.0
     * @apiName ShowTaskComment
     * @apiGroup Task Comment
     *
     * @apiParam {Integer}  id     Task Comment id
     *
     * @apiRequestExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiSuccess {Object}  object TaskComment
     * @apiSuccess {Integer} object.id
     *
     * @apiSuccessExample {json} Answer Example
     * {
     *   "id": 1,
     *   "task_id": 1,
     *   "start_at": "2006-05-31 16:15:09",
     *   "end_at": "2006-05-31 16:20:07",
     *   "created_at": "2018-09-25 06:15:08",
     *   "updated_at": "2018-09-25 06:15:08",
     *   "deleted_at": null,
     *   "count_mouse": 88,
     *   "count_keyboard": 127,
     *   "user_id": 1
     * }
     *
     * @apiUse UnauthorizedError
     */


    /**
     * @api {delete, post} /api/v1/task-comment/remove Destroy
     * @apiDescription Destroy Task Comment
     * @apiVersion 0.1.0
     * @apiName DestroyTaskComment
     * @apiGroup Task Comment
     *
     * @apiParam {Integer}   id Task Comment id
     *
     * @apiSuccess {String} message Message
     *
     * @apiSuccessExample {json} Answer Example
     * {
     *   "message":"Item has been removed"
     * }
     *
     * @apiUse UnauthorizedError
     */
    public function destroy(Request $request): JsonResponse
    {
        $itemId = Filter::process($this->getEventUniqueName('request.item.destroy'), $request->get('id'));
        $idInt = is_int($itemId);

        if (!$idInt) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.destroy'), [
                    'error' => 'Validation fail',
                    'reason' => 'Id invalid',
                ]),
                400
            );
        }

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(), ['id' => $itemId]
            )
        );

        $user = Auth::user();
        $full_access = $user->allowed('task-comment', 'full_access');

        if (!$full_access) {
            $itemsQuery->where(['user_id' => $user->id])
            ->whereHas('task', function ($taskQuery) use ($user) {
                $taskQuery->where(['user_id' => $user->id]);
            });
        }

        /** @var \Illuminate\Database\Eloquent\Model $item */
        $item = $itemsQuery->firstOrFail();
        $item->delete();

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                'message' => 'Item has been removed'
            ])
        );
    }

}
