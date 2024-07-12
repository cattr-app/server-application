<?php

namespace App\Http\Controllers\Api;

use App\Enums\Role;
use App\Http\Requests\Status\DestroyStatusRequest;
use App\Http\Requests\TaskComment\CreateTaskCommentRequest;
use App\Http\Requests\TaskComment\DestroyTaskCommentRequest;
use App\Http\Requests\TaskComment\ListTaskCommentRequest;
use App\Http\Requests\TaskComment\ShowTaskCommentRequestStatus;
use App\Http\Requests\TaskComment\UpdateTaskCommentRequest;
use Filter;
use App\Models\TaskComment;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class TaskCommentController extends ItemController
{
    protected const MODEL = TaskComment::class;

    /**
     * @api            {post} /task-comment/create Create Task Comment
     * @apiDescription Create a new task comment
     *
     * @apiVersion     4.0.0
     * @apiName        CreateTaskComment
     * @apiGroup       TaskComments
     *
     *
     * @apiPermission   task_comment_create
     * @apiPermission   task_comment_full_access
     * @apiParam {Integer} task_id ID of the task
     * @apiParam {String}  comment The content of the comment
     *
     * @apiParamExample {json} Request Example:
     *     {
     *       "task_id": 1,
     *       "comment": "This is a new comment"
     *     }
     *
     * @apiSuccess {Integer}  id          ID of the created comment
     * @apiSuccess {Integer}  task_id     ID of the task
     * @apiSuccess {Integer}  user_id     ID of the user who created the comment
     * @apiSuccess {String}   comment     The content of the comment
     * @apiSuccess {String}   created_at  Creation timestamp
     * @apiSuccess {String}   updated_at  Last update timestamp
     *
     * @apiSuccessExample {json} Response Example:
     *  HTTP/1.1 201 Created
     *  {
     *      "id": 1,
     *      "task_id": 1,
     *      "user_id": 1,
     *      "comment": "This is a new comment",
     *      "created_at": "2024-07-09T10:00:00.000000Z",
     *      "updated_at": "2024-07-09T10:00:00.000000Z"
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */
    public function create(CreateTaskCommentRequest $request): JsonResponse
    {
        Filter::listen(
            Filter::getRequestFilterName(),
            static function (array $data) use ($request) {
                $data['user_id'] = $request->user()->id;

                return $data;
            }
        );

        return $this->_create($request);
    }

    /**
     * @api            {post} /task-comment/edit Edit Task Comment
     * @apiDescription Edit an existing task comment
     *
     * @apiVersion     4.0.0
     * @apiName        EditTaskComment
     * @apiGroup       TaskComments
     *
     *
     * @apiParam {Integer} id      ID of the comment to edit
     * @apiParam {String}  comment The updated content of the comment
     *
     * @apiParamExample {json} Request Example:
     *     {
     *       "id": 1,
     *       "comment": "This is the updated comment"
     *     }
     *
     * @apiSuccess {Integer}  id          ID of the edited comment
     * @apiSuccess {Integer}  task_id     ID of the task
     * @apiSuccess {Integer}  user_id     ID of the user who edited the comment
     * @apiSuccess {String}   comment     The updated content of the comment
     * @apiSuccess {String}   created_at  Creation timestamp
     * @apiSuccess {String}   updated_at  Last update timestamp
     * @apiSuccess {String}   deleted_at  Deletion timestamp (if applicable, otherwise null)
     *
     * @apiSuccessExample {json} Response Example:
     *  HTTP/1.1 200 OK
     * {
     *     "id": 1,
     *     "task_id": 1,
     *     "user_id": 1,
     *     "content": "2344",
     *     "created_at": "2024-05-03T10:45:36.000000Z",
     *     "updated_at": "2024-05-03T10:45:36.000000Z",
     *     "deleted_at": null
     * }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     */
    public function edit(UpdateTaskCommentRequest $request): JsonResponse
    {
        return $this->_edit($request);
    }
    /**
     * @api            {any} /task-comment/list List Task Comments
     * @apiDescription Get list of Task Comments
     *
     * @apiVersion     4.0.0
     * @apiName        GetTaskCommentList
     * @apiGroup       Task Comments
     *
     * @apiPermission task_comment_list
     * @apiPermission task_comment_full_access
     *
     * @apiParam {Integer} [task_id] Optional task ID to filter comments
     *
     * @apiParamExample {json} Request Example:
     *     {
     *       "task_id": 1
     *     }
     *
     * @apiSuccess {Integer}  id            ID of the comment
     * @apiSuccess {Integer}  task_id       ID of the task
     * @apiSuccess {Integer}  user_id       ID of the user who created the comment
     * @apiSuccess {String}   content       Content of the comment
     * @apiSuccess {String}   created_at    Creation timestamp
     * @apiSuccess {String}   updated_at    Last update timestamp
     * @apiSuccess {String}   deleted_at  Deletion timestamp (if applicable, otherwise null)
     * @apiSuccess {Object}   user          User who created the comment

     *
     * @apiSuccessExample {json} Response Example:
     *  HTTP/1.1 200 OK
     *      {
     *        "id": 1,
     *        "task_id": 1,
     *        "user_id": 1,
     *        "content": "2344",
     *        "created_at": "2024-05-03T10:45:36.000000Z",
     *        "updated_at": "2024-05-03T10:45:36.000000Z",
     *        "deleted_at": null,
     *        "user": {
     *          "id": 1,
     *          "full_name": "Admin",
     *          "email": "admin@cattr.app",
     *          "url": "",
     *          "company_id": 1,
     *          "avatar": "",
     *          "screenshots_active": 1,
     *          "manual_time": 0,
     *          "computer_time_popup": 300,
     *          "blur_screenshots": false,
     *          "web_and_app_monitoring": true,
     *          "screenshots_interval": 5,
     *          "active": 1,
     *          "deleted_at": null,
     *          "created_at": "2023-10-26T10:26:17.000000Z",
     *          "updated_at": "2024-02-15T19:06:42.000000Z",
     *          "timezone": null,
     *          "important": 0,
     *          "change_password": 0,
     *          "role_id": 0,
     *          "user_language": "en",
     *          "type": "employee",
     *          "invitation_sent": false,
     *          "nonce": 0,
     *          "client_installed": 0,
     *          "permanent_screenshots": 0,
     *          "last_activity": "2023-10-26 10:26:17",
     *          "online": false,
     *          "can_view_team_tab": true,
     *          "can_create_task": true
     *        }
     *    }
     *
     * @apiUse 400Error
     * @apiUse UnauthorizedError
     */
    public function index(ListTaskCommentRequest $request): JsonResponse
    {
        Filter::listen(
            Filter::getQueryFilterName(),
            static function ($query) use ($request) {
                if (!$request->user()->can('edit', TaskComment::class)) {
                    $query = $query->whereHas(
                        'task',
                        static fn (Builder $taskQuery) => $taskQuery->where(
                            'user_id',
                            '=',
                            $request->user()->id
                        )
                    );
                }

                return $query->with('user');
            }
        );

        return $this->_index($request);
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @throws Throwable
     * @api             {post} /task-comment/show Show
     * @apiDescription  Show Task Comment
     *
     * @apiVersion      1.0.0
     * @apiName         ShowTaskComment
     * @apiGroup        Task Comment
     *
     * @apiPermission   task_comment_show
     * @apiPermission   task_comment_full_access
     */
    public function show(ShowTaskCommentRequestStatus $request): JsonResponse
    {
        return $this->_show($request);
    }

    /**
     * @apiDeprecated   since 4.0.0
     * @api             {post} /task-comment/remove Destroy Task Comment
     * @apiDescription  Destroy a Task Comment
     *
     * @apiVersion      4.0.0
     * @apiName         DestroyTaskComment
     * @apiGroup        Task Comment
     *
     * @apiPermission   task_comment_remove
     * @apiPermission   task_comment_full_access
     *
     * @apiParam {Integer} id ID of the task comment to destroy
     *
     * @apiParamExample {json} Request Example:
     *     {
     *       "id": 1
     *     }
     *
     * @apiSuccess {Integer} status Response status code
     * @apiSuccess {Boolean} success Response success status
     * @apiSuccess {String} message Success message
     *
     * @apiUse 400Error
     * @apiUse UnauthorizedError
     */
    public function destroy(DestroyTaskCommentRequest $request): JsonResponse
    {
        $user = $request->user();

        Filter::listen(
            Filter::getQueryFilterName(),
            static fn($query) => $user->hasRole([Role::ADMIN, Role::MANAGER]) ? $query :
                $query->where(['user_id' => $user->id])
                ->whereHas('task', static fn($taskQuery) => $taskQuery->where(['user_id' => $user->id]))
        );

        return $this->_destroy($request);
    }
}
