<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\ProjectsRoles;
use Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;

/**
 * Class ProjectsRolesController
 *
 * @package App\Http\Controllers\Api\v1
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
            'role_id'    => 'required|exists:role,id',
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
     * @api {get} /api/v1/projects-roles/list List
     * @apiDescription Get list of Projects Roles relations
     * @apiVersion 0.1.0
     * @apiName GetProjectRolesList
     * @apiGroup ProjectRoles
     *
     * @apiParam {Integer} [project_id] `QueryParam` Project ID
     * @apiParam {Integer} [role_id]    `QueryParam` Role ID
     *
     * @apiSuccess {Object[]} ProjectRolesList ProjectRoles
     *
     * @apiUse UnauthorizedError
     *
     * @todo: add request example
     *
     * @apiSuccessExample {json} Response example
     * [
     *   {
     *     "project_id": 1,
     *     "role_id": 1,
     *     "created_at": "2018-10-25 08:41:35",
     *     "updated_at": "2018-10-25 08:41:35"
     *   }
     * ]
     *
     * @apiParamExample {json} Request example
     * {
     *   "": "",
     *   "": ""
     * }
     *
     * @param Request $request
     *
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/projects-roles/create Create
     * @apiDescription Create Project Roles relation
     *
     * @apiVersion 0.1.0
     *
     * @apiName CreateProjectRoles
     * @apiGroup ProjectRoles
     *
     * @apiUse DefaultBulkCreateErrorResponse
     * @apiUse UnauthorizedError
     *
     * @todo: add response and error example
     *
     * @apiErrorExample {json} Error example
     * {
     *   "error": "Validation fail",
     *     "reason": {
     *       "project_id": [
     *         "The selected project id is invalid."
     *     ],
     *     "role_id": [
     *       "The selected role id is invalid."
     *     ]
     *   }
     * }
     *
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "project_id": 1,
     *      "role_id": 1
     *  }
     *
     * @apiSuccessExample {json} Simple Response Example
     * [
     *   {
     *     "project_id": 1,
     *     "role_id": 1,
     *     "updated_at": "2018-10-17 08:28:18",
     *     "created_at": "2018-10-17 08:28:18",
     *     "id": 0
     *   }
     * ]
     *
     * @apiErrorExample {json} Error Example
     * {
     *   "error": "Validation fail",
     *   "reason": {
     *     "project_id": [
     *       "The selected project id is invalid."
     *     ],
     *     "role_id": [
     *       "The selected role id is invalid."
     *     ]
     *   }
     * }
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
     * @api {post} /api/v1/projects-roles/bulk-create BulkCreate
     * @apiDescription Multiple Create Project Roles relation
     * @apiVersion 0.1.0
     * @apiName BulkCreateProjectRoles
     * @apiGroup ProjectRoles
     *
     * @apiParam {Object[]}    array                   Project Roles
     * @apiParam {Object}      array.object            ProjectRole
     * @apiParam {Integer}     array.object.project_id Project id
     * @apiParam {Integer}     array.object.role_id    Role id
     *
     * @apiSuccess {Messages[]} array                  Project Roles objects
     *
     * @apiUse UnauthorizedError
     * @todo: add request and response example with error
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.create'), $request->all());
        $result = [];

        if (empty($requestData['relations'])) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.bulkEdit'), [
                    'error' => 'validation fail',
                    'reason' => 'relations is empty',
                ]),
                400
            );
        }

        $relations = $requestData['relations'];
        if (!is_array($relations)) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.bulkEdit'), [
                    'error' => 'validation fail',
                    'reason' => 'relations should be an array',
                ]),
                400
            );
        }

        $allowed_fields = array_flip([
            'project_id',
            'role_id',
        ]);

        foreach ($relations as $relation) {
            $relation = array_intersect_key($relation, $allowed_fields);

            $validator = Validator::make(
                $relation,
                Filter::process($this->getEventUniqueName('validation.item.create'), $this->getValidationRules())
            );

            if ($validator->fails()) {
                $result[] = Filter::process($this->getEventUniqueName('answer.error.item.create'), [
                    'error' => 'Validation fail',
                    'reason' => $validator->errors(),
                    'code' => 400
                ]);
                continue;
            }

            $cls = $this->getItemClass();

            $item = Filter::process(
                $this->getEventUniqueName('item.create'),
                $cls::firstOrCreate($this->filterRequestData($relation))
            );

            unset($item['id']);
            $result[] = $item;
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                'messages' => $result,
            ])
        );
    }

  /**
   * @api {remove, post} /api/v1/projects-roles/remove Destroy
   * @apiDescription Destroy Project Roles relation
   * @apiVersion 0.1.0
   * @apiName DestroyProjectRoles
   * @apiGroup ProjectRoles
   *
   * @apiParam      {Object}   object               `QueryParam`
   * @apiParam      {Integer}  object.project_id    `QueryParam`
   * @apiParam      {Integer}  object.role_id       `QueryParam`
   *
   * @apiParamExample {json} Request example
   * {
   *    "project_id": 1,
   *    "role_id": 1
   * }
   *
   * @apiSuccess    {Object}   object           message
   * @apiSuccess    {String}   object.message   body
   *
   * @apiSuccessExample {json} Response example
   * {
   *    "message": "Item has been removed"
   * }
   *
   * @apiUse DefaultDestroyRequestExample
   * @apiUse DefaultBulkDestroyErrorResponse
   * @apiUse DefaultDestroyResponse
   *
   * @apiUse UnauthorizedError
   *
   * @
   * @todo: add request and response example with error
   *
   * @param Request $request
   * @return JsonResponse
   *
   * @throws \Exception
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
                    'error' => 'Validation fail',
                    'reason' => $validator->errors()
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

        /** @var \Illuminate\Database\Eloquent\Model $item */
        $item = $itemsQuery->first();
        if ($item) {
            $item->delete();
        } else {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                    'error' => 'Item has not been removed',
                    'reason' => 'Item not found'
                ]),
                404
            );
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                'message' => 'Item has been removed'
            ])
        );
    }

    /**
     * @api {post} /api/v1/projects-roles/bulk-remove BulkDestroy
     * @apiDescription Multiple Destroy Project Roles relation
     * @apiVersion 0.1.0
     * @apiName BulkDestroyProjectRoles
     * @apiGroup ProjectRoles
     *
     * @apiParam   {Object[]}  array                   ProjectRoles
     * @apiParam   {Object}    array.object            Project Role relation
     * @apiParam   {Integer}   array.object.project_id Project id
     * @apiParam   {Integer}   array.object.role_id    Role id
     *
     * @apiSuccess {Object[]}  array                   Messages
     * @apiSuccess {Object}    array.object            Message
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.destroy'), $request->all());
        $result = [];

        if (empty($requestData['relations'])) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.bulkEdit'), [
                    'error' => 'validation fail',
                    'reason' => 'relations is empty',
                ]),
                400
            );
        }

        $relations = $requestData['relations'];
        if (!is_array($relations)) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.bulkEdit'), [
                    'error' => 'validation fail',
                    'reason' => 'relations should be an array',
                ]),
                400
            );
        }

        foreach ($relations as $relation) {
            /** @var Builder $itemsQuery */
            $itemsQuery = Filter::process(
                $this->getEventUniqueName('answer.success.item.query.prepare'),
                $this->applyQueryFilter(
                    $this->getQuery(), $relation
                )
            );

            $validator = Validator::make(
                $relation,
                Filter::process(
                    $this->getEventUniqueName('validation.item.edit'),
                    $this->getValidationRules()
                )
            );

            if ($validator->fails()) {
                $result[] = [
                    'error' => 'Validation fail',
                    'reason' => $validator->errors(),
                    'code' => 400,
                ];
                continue;
            }

            /** @var \Illuminate\Database\Eloquent\Model $item */
            $item = $itemsQuery->first();
            if ($item && $item->delete()) {
                $result[] = ['message' => 'Item has been removed'];
            } else {
                $result[] = [
                    'error' => 'Item has not been removed',
                    'reason' => 'Item not found'
                ];
             }
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                'messages' => $result
            ])
        );
    }
}
