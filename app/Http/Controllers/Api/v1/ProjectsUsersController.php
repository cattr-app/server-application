<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\ProjectsUsers;
use App\EventFilter\Facades\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Class ProjectsUsersController
 *
 * @package App\Http\Controllers\Api\v1
 */
class ProjectsUsersController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return ProjectsUsers::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'project_id' => 'required|exists:projects,id',
            'user_id' => 'required|exists:users,id',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'projects-users';
    }

    /**
     * @return array
     */
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

    /**
     * @apiDefine ProjectUserRelations
     * @apiParam {Object} [user]    `QueryParam` ProjectUser's relation user. All params in <a href="#api-User-GetUserList" >@User</a>
     * @apiParam {Object} [project] `QueryParam` ProjectUser's relation project. All params in <a href="#api-Project-GetProjectList" >@Project</a>
     */

    /**
     * @apiDefine ProjectUserRelationsExample
     * @apiParamExample {json} Request With Relations Example
     *  {
     *      "with":                 "project, user, project.tasks",
     *      "project.id":           [">", 1],
     *      "project.tasks.active": 1,
     *      "user.full_name":       ["like", "%lorem%"]
     *  }
     */

    /**
     * @param Request $request
     *
     * @return JsonResponse
     * @api {any} /api/v1/projects-users/list List
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "user_id":        ["=", [1,2,3]],
     *      "project_id":     [">", 1]
     *  }
     * @apiUse ProjectUserRelationsExample
     * @apiDescription Get list of Projects Users relations
     * @apiVersion 0.1.0
     * @apiName GetProjectUsersList
     * @apiGroup ProjectUsers
     *
     * @apiParam {Integer} [project_id] `QueryParam` Project-User Project id
     * @apiParam {Integer} [user_id]    `QueryParam` Project-User User id
     * @apiUse ProjectUserRelations
     *
     * @apiSuccess {Object[]}  ProjectUsersList                          Project-Users
     * @apiSuccess {Object}    ProjectUsersList.ProjectUser             Project-User
     * @apiSuccess {Integer}   ProjectUsersList.ProjectUser.user_id     Project-User User id
     * @apiSuccess {Integer}   ProjectUsersList.ProjectUser.project_id  Project-User Project id
     * @apiSuccess {String}    ProjectUsersList.ProjectUser.created_at  Project-User date time of create
     * @apiSuccess {String}    ProjectUsersList.ProjectUser.updated_at  Project-User date time of update
     * @apiSuccess {Object}    ProjectUsersList.ProjectUser.user        Project-User User
     * @apiSuccess {Object}    ProjectUsersList.ProjectUser.project     Project-User Project
     *
     * @apiUse UnauthorizedError
     *
     */

    /**
     * @param Request $request
     * @return JsonResponse
     * @api {post} /api/v1/projects-users/create Create
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "project_id": 1,
     *      "user_id": 45
     *  }
     * @apiDescription Create Project Users relation
     * @apiVersion 0.1.0
     * @apiName CreateProjectUsers
     * @apiGroup ProjectUsers
     *
     * @apiParam   {Integer}   project_id              Project-User Project id
     * @apiParam   {Integer}   user_id                 Project-User User id
     *
     * @apiSuccess {Integer}   array.object.user_id     Project-User User id
     * @apiSuccess {Integer}   array.object.project_id  Project-User Project id
     * @apiSuccess {String}    array.object.created_at  Project-User date time of create
     * @apiSuccess {String}    array.object.updated_at  Project-User date time of update
     *
     * @apiUse DefaultCreateErrorResponse
     * @apiUse UnauthorizedError
     *
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
                    'info' => $validator->errors(),
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
     * @param Request $request
     * @return JsonResponse
     * @api {post} /api/v1/projects-users/bulk-create BulkCreate
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "relations":
     *      [
     *          {
     *              "project_id":1,
     *              "user_id":3
     *          },
     *          {
     *              "project_id":1,
     *              "user_id":2
     *          }
     *      ]
     *  }
     *
     * @apiDescription Multiple Create Project Users relation
     * @apiVersion 0.1.0
     * @apiName BulkCreateProjectUsers
     * @apiGroup ProjectUsers
     *
     * @apiParam {Object[]} relations                   Project-User relations
     * @apiParam {Object}   relations.object            Object Project-User relation
     * @apiParam {Integer}  relations.object.project_id Project-User Project id
     * @apiParam {Integer}  relations.object.user_id    Project-User User id
     *
     * @apiSuccess {Object[]} messages                   Project-Users
     * @apiSuccess {Object}   messages.object            Project-User
     * @apiSuccess {Integer}  messages.object.user_id    Project-User User id
     * @apiSuccess {Integer}  messages.object.project_id Project-User Project id
     * @apiSuccess {String}   messages.object.created_at Project-User date time of create
     * @apiSuccess {String}   messages.object.updated_at Project-User date time of update
     *
     * @apiSuccessExample {json} Simple Response Example
     * {
     *   "messages": [
     *     {
     *       "project_id": 1,
     *       "user_id": 3,
     *       "updated_at": "2018-10-17 03:58:05",
     *       "created_at": "2018-10-17 03:58:05",
     *       "id": 0
     *     },
     *     {
     *       "project_id": 1,
     *       "user_id": 2,
     *       "created_at": "2018-10-17 03:58:05",
     *       "updated_at": "2018-10-17 03:58:05"
     *     }
     *   ]
     * }
     *
     * @apiUse DefaultBulkCreateErrorResponse
     * @apiUse UnauthorizedError
     *
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.create'), $request->all());
        $result = [];

        if (empty($requestData['relations'])) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.bulkEdit'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => 'relations is empty',
                ]), 400);
        }

        $relations = $requestData['relations'];
        if (!is_array($relations)) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.bulkEdit'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => 'relations should be an array',
                ]), 400);
        }

        $allowed_fields = array_flip([
            'project_id',
            'user_id',
        ]);

        foreach ($relations as $relation) {
            $relation = array_intersect_key($relation, $allowed_fields);

            $validator = Validator::make(
                $relation,
                Filter::process($this->getEventUniqueName('validation.item.create'), $this->getValidationRules())
            );

            if ($validator->fails()) {
                $result[] = Filter::process($this->getEventUniqueName('answer.error.item.create'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors(),
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
     * @param Request $request
     * @return JsonResponse
     *
     * @throws \Exception
     * @api {delete, post} /api/v1/projects-users/remove Destroy
     * @apiDescription Destroy Project Users relation
     * @apiParamExample {json} Simple Request Example
     *  {
     *      "project_id":1,
     *      "user_id":4
     *  }
     * @apiVersion 0.1.0
     * @apiName DestroyProjectUsers
     * @apiGroup ProjectUsers
     *
     * @apiParam             {Integer} project_id           Project-User Project id
     * @apiParam             {Integer} user_id              Project-User User id
     *
     * @apiSuccess {String} message Message about success item remove
     *
     * @apiSuccessExample {json} Simple Response Example
     * {
     *    "message": "Item has been removed"
     * }
     *
     * @apiError (Error 400) {String} error     Name of error
     * @apiError (Error 400) {String} reason    Reason of error
     *
     * @apiUse UnauthorizedError
     *
     * @apiErrorExample {json} Simple Error Example
     * {
     *   "error": "Item has not been removed",
     *   "reason": "Item not found"
     * }
     *
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
                    'info' => $validator->errors(),
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
                    'message' => 'Item not found'
                ]), 404);
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                'success' => true,
                'message' => 'Item has been removed'
            ])
        );
    }

    /**
     * @param Request $request
     * @return JsonResponse
     *
     * @throws \Exception
     * @api {post} /api/v1/projects-users/bulk-remove BulkDestroy
     * @apiParamExample {json} Simple Request Example
     * {
     *  "relations":
     *  [
     *      {
     *          "project_id": 1,
     *          "user_id": 4
     *      },
     *      {
     *          "project_id": 2,
     *          "user_id": 4
     *      }
     *  ]
     * }
     * @apiDescription Multiple Destroy Project Users relation
     * @apiVersion 0.1.0
     * @apiName BulkDestroyProjectUsers
     * @apiGroup ProjectUsers
     *
     * @apiParam    {Object[]} relations                    Project-User relations
     * @apiParam    {Object}   relations.object             Object Project-User relation
     * @apiParam    {Integer}  relations.object.project_id  Project-User Project id
     * @apiParam    {Integer}  relations.object.user_id     Project-User User id
     *
     * @apiSuccess  {Object[]} messages                     Messages
     * @apiSuccess  {Object}   messages.object Item removal Message status
     *
     * @apiUse DefaultBulkDestroyErrorResponse
     *
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.destroy'), $request->all());
        $result = [];

        if (empty($requestData['relations'])) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.bulkEdit'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => 'relations is empty',
                ]), 400);
        }

        $relations = $requestData['relations'];
        if (!is_array($relations)) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.bulkEdit'), [
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => 'relations should be an array',
                ]), 400);
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
                    'success' => false,
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors(),
                    'code' => 400
                ];
                continue;
            }

            /** @var Model $item */
            $item = $itemsQuery->first();
            if ($item && $item->delete()) {
                $result[] = ['success' => true, 'message' => 'Item has been removed'];
            } else {
                $result[] = [
                    'success' => false,
                    'error_type' => 'query.item_not_found',
                    'message' => 'Item not found',
                    'code' => 404
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
