<?php

namespace App\Http\Controllers\Api\v1;

use App\EventFilter\Facades\Filter;
use App\Models\ProjectsUsers;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class ProjectsUsersController extends ItemController
{
    public static function getControllerRules(): array
    {
        return [
            'index' => 'projects-users.list',
            'count' => 'projects-users.list',
            'create' => 'projects-users.create',
            'bulkCreate' => 'projects-users.bulk-create',
            'destroy' => 'projects-users.remove',
            'bulkDestroy' => 'projects-users.bulk-remove',
        ];
    }

    public function getEventUniqueNamePart(): string
    {
        return 'projects-users';
    }

    public function create(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.create'), $request->all());

        $validator = Validator::make(
            $requestData,
            Filter::process($this->getEventUniqueName('validation.item.create'), $this->getValidationRules())
        );

        if ($validator->fails()) {
            return new JsonResponse(
                Filter::process($this->getEventUniqueName('answer.error.item.create'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors(),
                ]),
                400
            );
        }

        $cls = $this->getItemClass();

        $item = Filter::process(
            $this->getEventUniqueName('item.create'),
            $cls::firstOrCreate($this->filterRequestData($requestData))
        );

        return new JsonResponse(
            Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                $item,
            ])
        );
    }

    public function getValidationRules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'user_id' => 'required|exists:users,id',
        ];
    }

    /**
     * @api             {get,post} /v1/projects-users/list List
     * @apiDescription  Get list of Projects Users relations
     *
     * @apiVersion      1.0.0
     * @apiName         List
     * @apiGroup        Project Users
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   projects_users_list
     * @apiPermission   projects_users_full_access
     *
     * @apiUse          ProjectUserParams
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "user_id": ["=", [1,2,3]],
     *    "project_id": [">", 1]
     *  }
     *
     * @apiUse          ProjectUserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  [
     *    {
     *      "project_id": 1,
     *      "user_id": 3,
     *      "created_at": "2020-01-27T07:29:19+00:00",
     *      "updated_at": "2020-01-27T07:29:19+00:00",
     *      "role_id": 1
     *    }
     *  ]
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ForbiddenError
     */

    /**
     * @api             {post} /v1/projects-users/create Create
     * @apiDescription  Create Project Users relation
     *
     * @apiVersion      1.0.0
     * @apiName         Create
     * @apiGroup        Project Users
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   projects_users_create
     * @apiPermission   projects_users_full_access
     *
     * @apiParam  {Integer}  project_id  Project-User Project id
     * @apiParam  {Integer}  user_id     Project-User User id
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *    "project_id": 1,
     *    "user_id": 45
     *  }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Object}   res      Project-User
     *
     * @apiUse          ProjectUserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "res": {
     *      "project_id": 1,
     *      "user_id": 45,
     *      "created_at": "2020-01-27T07:29:19+00:00",
     *      "updated_at": "2020-01-27T07:29:19+00:00",
     *      "role_id": 1
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     */

    public function getItemClass(): string
    {
        return ProjectsUsers::class;
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /v1/projects-users/bulk-create Bulk Create
     * @apiDescription  Multiple Create Project Users relation
     *
     * @apiVersion      1.0.0
     * @apiName         Bulk Create
     * @apiGroup        Project Users
     *
     * @apiPermission   projects_users_bulk_create
     * @apiPermission   projects_users_full_access
     */

    /**
     * @api             {get,post} /v1/projects-users/count Count
     * @apiDescription  Count Project Users
     *
     * @apiVersion      1.0.0
     * @apiName         Count
     * @apiGroup        Project Users
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   total    Amount of projects that we have
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "total": 2
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */

    /**
     * @api             {post} /v1/projects-users/remove Destroy
     * @apiDescription  Destroy Project Users relation
     *
     * @apiVersion      1.0.0
     * @apiName         Destroy
     * @apiGroup        Project Users
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   projects_users_remove
     * @apiPermission   projects_users_full_access
     *
     * @apiParam  {Integer}  project_id  Project ID
     * @apiParam  {Integer}  user_id     User ID
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *    "project_id": 1,
     *    "user_id": 4
     *  }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   message  Destroy status
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "message": "Item has been removed"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     * @apiUse          ItemNotFoundError
     */

    /**
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
            return new JsonResponse(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors(),
                ]),
                400
            );
        }

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(),
                $requestData
            )
        );

        /** @var Model $item */
        $item = $itemsQuery->first();
        if (!$item) {
            return new JsonResponse(
                Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                    'success' => false,
                    'error_type' => 'query.item_not_found',
                    'message' => 'Item not found'
                ]),
                404
            );
        }

        $item->delete();

        return new JsonResponse(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                'success' => true,
                'message' => 'Item has been removed'
            ])
        );
    }

    /**
     * @api             {post} /v1/projects-users/bulk-remove Bulk Destroy
     * @apiDescription  Multiple Destroy Project Users relation
     *
     * @apiVersion      1.0.0
     * @apiName         Bulk Destroy
     * @apiGroup        Project Users
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   projects_users_bulk_remove
     * @apiPermission   projects_users_full_access
     *
     * @apiParam  {Object[]}  relations             Project-User relations
     * @apiParam  {Integer}   relations.project_id  Project ID
     * @apiParam  {Integer}   relations.user_id     User ID
     *
     * @apiParamExample {json} Simple Request Example
     * {
     *  "relations": [
     *    {
     *      "project_id": 1,
     *      "user_id": 4
     *    },
     *    {
     *      "project_id": 2,
     *      "user_id": 4
     *    }
     *  ]
     * }
     *
     * @apiSuccess {Boolean}    success    Indicates successful request when `TRUE`
     * @apiSuccess {String}     message    Message from server
     * @apiSuccess {Integer[]}  removed    Removed relations
     * @apiSuccess {Integer[]}  not_found  Not found relations
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "message": "Items successfully removed",
     *    "removed": [12, 123, 45],
     *  }
     *
     * @apiSuccessExample {json} Not all intervals removed Response Example
     *  HTTP/1.1 207 Multi-Status
     *  {
     *    "success": true,
     *    "message": "Some items have not been removed",
     *    "removed": [12, 123, 45],
     *    "not_found": [154, 77, 66]
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */
    /**
     * @throws Exception
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $validationRules = [
            'relations' => 'required|array',
            'relations.*.user_id' => 'required|integer',
            'relations.*.project_id' => 'required|integer',
        ];

        $validator = Validator::make(
            Filter::process($this->getEventUniqueName('request.item.destroy'), $request->all()),
            Filter::process($this->getEventUniqueName('validation.item.destroy'), $validationRules)
        );

        if ($validator->fails()) {
            return new JsonResponse(
                Filter::process($this->getEventUniqueName('answer.error.item.bulkDestroy'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]),
                400
            );
        }

        //filter excess params if they are provided
        $relations = collect($request->get('relations'))->map(static function ($relation) {
            return Arr::only($relation, ['project_id', 'user_id']);
        });

        $filters = [
            'project_id' => ['in', $relations->pluck('project_id')->toArray()],
            'user_id' => ['in', $relations->pluck('user_id')->toArray()]
        ];

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter($this->getQuery(), $filters)
        );

        $foundRelations = $itemsQuery->get()->map->only(['project_id', 'user_id']);

        $notFoundRelations = $relations->filter(static function ($item) use ($foundRelations) {
            return !$foundRelations->contains($item);
        });

        $itemsQuery->delete();

        $responseData = [
            'success' => true,
            'message' => 'Items successfully removed',
            'removed' => $foundRelations->values()
        ];

        if ($notFoundRelations->isNotEmpty()) {
            $responseData['message'] = 'Some items have not been removed';
            $responseData['not_found'] = $notFoundRelations->values();
        }

        return new JsonResponse(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), $responseData),
            ($notFoundRelations->isNotEmpty()) ? 207 : 200
        );
    }
}
