<?php

namespace App\Http\Controllers\Api\v1;

use App\EventFilter\Facades\Filter;
use App\Models\ProjectsRoles;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Class ProjectsRolesController
 * @deprecated
 * @codeCoverageIgnore
 *
 * @deprecated
 * @codeCoverageIgnore
 */
class ProjectsRolesController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return ProjectsRoles::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'role_id' => 'required|exists:role,id',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'projects-roles';
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'index' => 'projects-roles.list',
            'count' => 'projects-roles.list',
            'create' => 'projects-roles.create',
            'bulkCreate' => 'projects-roles.bulk-create',
            'destroy' => 'projects-roles.remove',
            'bulkDestroy' => 'projects-roles.bulk-remove',
        ];
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {get} /v1/projects-roles/list List
     * @apiDescription  Get list of Projects Roles relations
     *
     * @apiVersion      1.0.0
     * @apiName         GetProjectRolesList
     * @apiGroup        ProjectRoles
     *
     * @apiPermission   projects_roles_list
     * @apiPermission   projects_roles_full_access
     */

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /v1/projects-roles/create Create
     * @apiDescription  Create Project Roles relation
     *
     * @apiVersion      1.0.0
     * @apiName         CreateProjectRoles
     * @apiGroup        ProjectRoles
     *
     * @apiPermission   projects_roles_create
     * @apiPermission   projects_roles_full_access
     */
    /**
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
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]), 400);
        }

        $cls = $this->getItemClass();

        $item = Filter::process(
            $this->getEventUniqueName('item.create'),
            $cls::firstOrCreate($this->filterRequestData($requestData))
        );

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                $item,
            ])
        );
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /v1/projects-roles/remove Destroy
     * @apiDescription  Destroy Project Roles relation
     *
     * @apiVersion      1.0.0
     * @apiName         DestroyProjectRoles
     * @apiGroup        ProjectRoles
     *
     * @apiPermission   projects_roles_remove
     * @apiPermission   projects_roles_full_access
     */
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.destroy'), $request->all());

        $validator = Validator::make(
            $requestData,
            Filter::process(
                $this->getEventUniqueName('validation.item.edit'),
                $this->getValidationRules()
            )
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]),
                400
            );
        }

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(), $requestData
            )
        );

        /** @var Model $item */
        $item = $itemsQuery->first();
        if ($item) {
            $item->delete();
        } else {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                    'success' => false,
                    'error_type' => 'query.item_not_found',
                    'message' => 'Item not found',
                ]), 404);
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                'message' => 'Item has been removed'
            ])
        );
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /v1/projects-roles/bulk-create Bulk Create
     * @apiDescription  Multiple Create Project Roles relation
     *
     * @apiVersion      1.0.0
     * @apiName         BulkCreateProjectRoles
     * @apiGroup        ProjectRoles
     *
     * @apiPermission   projects_roles_bulk_create
     * @apiPermission   projects_roles_full_access
     */
    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /v1/projects-roles/bulk-remove Bulk Destroy
     * @apiDescription  Multiple Destroy Project Roles relation
     *
     * @apiVersion      1.0.0
     * @apiName         BulkDestroyProjectRoles
     * @apiGroup        ProjectRoles
     *
     * @apiPermission   projects_roles_bulk_remove
     * @apiPermission   projects_roles_full_access
     */
    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /v1/projects-roles/count Count
     * @apiDescription  Count Project Roles relation
     *
     * @apiVersion      1.0.0
     * @apiName         CountProjectRoles
     * @apiGroup        ProjectRoles
     */
}
