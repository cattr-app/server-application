<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Project;
use App\Models\Role;
use App\User;
use Auth;
use Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Route;

/**
 * Class ProjectController
 *
 * @package App\Http\Controllers\Api\v1
 */
class ProjectController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return Project::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'company_id'  => 'required',
            'name'        => 'required',
            'description' => 'required',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'project';
    }

    /**
     * @return string[]
     */
    public function getQueryWith(): array
    {
        return ['users'];
    }

    /**
     * @api {any} /api/v1/projects/list List
     * @apiDescription Get list of Projects
     * @apiVersion 0.1.0
     * @apiName GetProjectList
     * @apiGroup Project
     *
     * @apiParam {Integer}  [id]          `QueryParam`                    Project ID
     * @apiParam {String}   [name]        `QueryParam`                    Project Name
     * @apiParam {String}   [description] `QueryParam`                    Project Description
     * @apiParam {Integer}  [company_id]  `QueryParam`                    Project Company's ID
     * @apiParam {DateTime} [created_at]  `QueryParam`                    Project Creation DateTime
     * @apiParam {DateTime} [updated_at]  `QueryParam`                    Last Project update DataTime
     * @apiParam {DateTime} [deleted_at]  `QueryParam`                    When Project was deleted (null if not)
     *
     * @apiSuccess (200) {Project[]} ProjectList array of Project objects
     *
     * @param Request $request
     *
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/projects/create Create
     * @apiDescription Create Project
     * @apiVersion 0.1.0
     * @apiName CreateProject
     * @apiGroup Project
     */

    /**
     * @api {any} /api/v1/projects/show Show
     * @apiDescription Show Project
     * @apiVersion 0.1.0
     * @apiName ShowProject
     * @apiGroup Project
     */

    /**
     * @api {post} /api/v1/projects/edit Edit
     * @apiDescription Edit Project
     * @apiVersion 0.1.0
     * @apiName EditProject
     * @apiGroup Project
     */

    /**
     * @api {post} /api/v1/projects/destroy Destroy
     * @apiDescription Destroy Project
     * @apiVersion 0.1.0
     * @apiName DestroyProject
     * @apiGroup Project
     */


    /**
     * @api {any} /api/v1/projects/relations Relations
     * @apiDescription Show attached projects to user
     * @apiVersion 0.1.0
     * @apiName RelationsProject
     * @apiGroup Project
     *
     * @apiParam {Integer} user_id Attached User ID
     *
     * @apiSuccess {Object[]} array        Array of Project object
     * @apiSuccess {Object}   array.object Project object
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function relations(Request $request): JsonResponse
    {
        $userId = is_int($request->get('user_id')) ? $request->get('user_id') : false;
        $attachedUsersId = collect(Auth::user()->projects)->flatMap(function($project) {
           return collect($project->users)->pluck('id');
        });

        if (!$userId) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.error.item.relations'),
                [
                    'error' => 'Validation fail',
                    'reason' => 'user_id is invalid',
                ]),
                400
            );
        }

        if (!collect($attachedUsersId)->contains($userId)) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.success.item.relations'),
                []
            ));
        }

        $filters = [
            'users.id' => $userId,
            'tasks.user_id' => $userId,
            'tasks.timeIntervals.user_id' => $userId
        ];

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.relations.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(), $filters
            )
        );

        /** @var User[] $rules */
        $projects = $itemsQuery->get();

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.relations'),
            $projects
        ));
    }


    /**
     * @param bool $withRelations
     *
     * @return Builder
     */
    protected function getQuery($withRelations = false): Builder
    {
        $query = parent::getQuery($withRelations);
        $full_access = Role::can(Auth::user(), 'projects', 'full_access');
        $user_relations_access = Role::can(Auth::user(), 'users', 'relations');
        $project_relations_access = Role::can(Auth::user(), 'projects', 'relations');
        $action_method = Route::getCurrentRoute()->getActionMethod();

        if ($full_access) {
            return $query;
        }

        $user_projects_id = collect(Auth::user()->projects)->pluck('id');
        $projects_id = collect($user_projects_id);

        if ($project_relations_access && $action_method !== 'edit' && $action_method !== 'remove') {
            $user_tasks_project_id = collect(Auth::user()->tasks)->flatMap(function ($task) {
                if ($task->project) {
                    return collect($task->project->id);
                }
                return null;
            });
            $user_time_interval_project_id = collect(Auth::user()->timeIntervals)->flatMap(function ($val) {
                if ($val->task->project) {
                    return collect($val->task->project->id);
                }
                return null;
            });
            $projects_id = collect([$projects_id, $user_tasks_project_id, $user_time_interval_project_id])->collapse();
        }

        if ($user_relations_access) {
            $attached_users_project_id = collect(Auth::user()->attached_users)->flatMap(function($user) {
                return collect($user->projects)->pluck('id');
            });
            $projects_id = collect([$projects_id, $attached_users_project_id])->collapse()->unique();
        }

        $query->whereIn('projects.id', $projects_id);
        return $query;
    }
}
