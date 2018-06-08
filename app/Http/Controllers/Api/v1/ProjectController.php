<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Project;
use App\Models\Role;
use Auth;
use Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * @api {post} /api/v1/projects/list List
     * @apiDescription Get list of Projects
     * @apiVersion 0.1.0
     * @apiName GetProjectList
     * @apiGroup Project
     *
     * @apiParam {Integer}  [id]          `QueryParam` Project ID
     * @apiParam {Integer}  [user_id]     `QueryParam` Project's Users ID
     * @apiParam {String}   [name]        `QueryParam` Project Name
     * @apiParam {String}   [description] `QueryParam` Project Description
     * @apiParam {Integer}  [company_id]  `QueryParam` Project Company's ID
     * @apiParam {DateTime} [created_at]  `QueryParam` Project Creation DateTime
     * @apiParam {DateTime} [updated_at]  `QueryParam` Last Project update DataTime
     * @apiParam {DateTime} [deleted_at]  `QueryParam` When Project was deleted (null if not)
     *
     * @apiSuccess (200) {Project[]} ProjectList array of Project objects
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.list'), $request->all());
        $request->get('user_id') ? $requestData['users.id'] = $request->get('user_id') : False;
        unset($requestData['user_id']);

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(), $requestData ?: []
            )
        );

        return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.item.list.result'),
                $itemsQuery->get()
            )
        );
    }

    /**
     * @api {post} /api/v1/projects/create Create
     * @apiDescription Create Project
     * @apiVersion 0.1.0
     * @apiName CreateProject
     * @apiGroup Project
     */

    /**
     * @api {post} /api/v1/projects/show Show
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
     * @param bool $withRelations
     *
     * @return Builder
     */
    protected function getQuery($withRelations = true): Builder
    {
        $query = parent::getQuery($withRelations);
        $full_access = Role::can(Auth::user(), 'projects', 'full_access');
        $relations_access = Role::can(Auth::user(), 'users', 'relations');

        if ($full_access) {
            return $query->without('users');
        }

        $user_projects_id = collect(Auth::user()->projects)->pluck('id');

        if ($relations_access) {
            $attached_users_project_id = collect(Auth::user()->attached_users)->flatMap(function($val) {
                return collect($val->projects)->pluck('id');
            });
            $projects_id = collect([$user_projects_id, $attached_users_project_id])->collapse()->unique();
            $query->whereIn('projects.id', $projects_id);
        } else {
            $query->whereIn('projects.id', $user_projects_id);
        }

        return $query->without('users');
    }
}
