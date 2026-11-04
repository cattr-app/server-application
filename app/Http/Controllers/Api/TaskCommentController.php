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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class TaskCommentController extends ItemController
{
    protected const MODEL = TaskComment::class;

    /**
     * Create Task Comment
     *
     * Create a new task comment
     *
     * Required permission (any of): `task_comment_create`, `task_comment_full_access`
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
     * Edit Task Comment
     *
     * Edit an existing task comment
     */
    public function edit(UpdateTaskCommentRequest $request): JsonResponse
    {
        return $this->_edit($request);
    }
    /**
     * List Task Comments
     *
     * Get list of Task Comments
     *
     * Required permission (any of): `task_comment_list`, `task_comment_full_access`
     */
    public function index(ListTaskCommentRequest $request): JsonResponse
    {
        Filter::listen(
            Filter::getQueryFilterName(),
            // Same rule as show(): only comments on tasks visible to the user
            static fn($query) => $query->whereHas('task')->with('user')
        );

        return $this->_index($request);
    }

    /**
     * Show
     *
     * Show Task Comment
     *
     * Required permission (any of): `task_comment_show`, `task_comment_full_access`
     *
     * @deprecated since 1.0.0
     */
    public function show(ShowTaskCommentRequestStatus $request): JsonResponse
    {
        // Only comments on tasks visible to the user: the task's global access scope applies inside whereHas
        Filter::listen(Filter::getQueryFilterName(), static fn($query) => $query->whereHas('task'));

        return $this->_show($request);
    }

    /**
     * Destroy Task Comment
     *
     * Destroy a Task Comment
     *
     * Required permission (any of): `task_comment_remove`, `task_comment_full_access`
     *
     * @deprecated since 4.0.0
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
