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
     * @throws Throwable
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
     * @throws Throwable
     */
    public function edit(UpdateTaskCommentRequest $request): JsonResponse
    {
        return $this->_edit($request);
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /task-comment/create Create
     * @apiDescription  Create Task Comment
     *
     * @apiVersion      1.0.0
     * @apiName         CreateTaskComment
     * @apiGroup        Task Comment
     *
     * @apiPermission   task_comment_create
     * @apiPermission   task_comment_full_access
     */

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /task-comment/list List
     * @apiDescription  Get list of Task Comments
     *
     * @apiVersion      1.0.0
     * @apiName         GetTaskCommentList
     * @apiGroup        Task Comment
     *
     * @apiPermission   task_comment_list
     * @apiPermission   task_comment_full_access
     */
    /**
     * @throws Exception
     */
    public function index(ListTaskCommentRequest $request): JsonResponse
    {
        Filter::listen(
            Filter::getQueryFilterName(),
            static function ($query) use ($request) {
                if (!$request->user()->can('edit', TaskComment::class)) {
                    $query = $query->whereHas(
                        'task',
                        static fn(Builder $taskQuery) => $taskQuery->where(
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
     * @apiDeprecated   since 1.0.0
     * @api             {post} /task-comment/remove Destroy
     * @apiDescription  Destroy Task Comment
     *
     * @apiVersion      1.0.0
     * @apiName         DestroyTaskComment
     * @apiGroup        Task Comment
     *
     * @apiPermission   task_comment_remove
     * @apiPermission   task_comment_full_access
     */
    /**
     * @throws Throwable
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
