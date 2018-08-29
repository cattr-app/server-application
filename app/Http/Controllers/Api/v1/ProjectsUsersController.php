<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\ProjectsUsers;
use Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;

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
            'user_id'    => 'required|exists:users,id',
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
     * @apiDefine ProjectUserRelations
     * @apiParam {Object} [user]    `QueryParam` ProjectUser's relation user. All params in <a href="#api-User-GetUserList" >@User</a>
     * @apiParam {Object} [project] `QueryParam` ProjectUser's relation project. All params in <a href="#api-Project-GetProjectList" >@Project</a>
     */

    /**
     * @apiDefine ProjectUserRelationsExample
     * @apiParamExample {json} Request-With-Relations-Example:
     *  {
     *      "with":                 "project, user, project.tasks",
     *      "project.id":           [">", 1],
     *      "project.tasks.active": 1,
     *      "user.full_name":       ["like", "%lorem%"]
     *  }
     */

    /**
     * @api {any} /api/v1/projects-users/list List
     * @apiParamExample {json} Simple-Request-Example:
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
     * @apiParam {Integer} [project_id] `QueryParam` Project-User's Project ID
     * @apiParam {Integer} [user_id]    `QueryParam` Project-User's User ID
     * @apiUse ProjectUserRelations
     *
     * @apiSuccess {Objects[]} ProjectUsersList                         Array of Project-Users objects
     * @apiSuccess {Objects}   ProjectUsersList.ProjectUser             Project-User object
     * @apiSuccess {Integer}   ProjectUsersList.ProjectUser.user_id     Project-User's User ID
     * @apiSuccess {Integer}   ProjectUsersList.ProjectUser.project_id  Project-User's Project ID
     * @apiSuccess {DateTime}  ProjectUsersList.ProjectUser.created_at  Project-User's date time of create
     * @apiSuccess {DateTime}  ProjectUsersList.ProjectUser.updated_at  Project-User's date time of update
     * @apiSuccess {Object}    ProjectUsersList.ProjectUser.user        Project-User's User
     * @apiSuccess {Object}    ProjectUsersList.ProjectUser.project     Project-User's Project
     *
     * @param Request $request
     *
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/projects-users/create Create
     * @apiParamExample {json} Simple-Request-Example:
     *  {
     *      "project_id": 1,
     *      "user_id": 45
     *  }
     * @apiDescription Create Project Users relation
     * @apiVersion 0.1.0
     * @apiName CreateProjectUsers
     * @apiGroup ProjectUsers
     *
     * @apiParam {Integer} project_id Project-User's Project ID
     * @apiParam {Integer} user_id    Project-User's User ID
     *
     * @apiSuccess {Integer}   ProjectUsersList.ProjectUser.user_id     Project-User's User ID
     * @apiSuccess {Integer}   ProjectUsersList.ProjectUser.project_id  Project-User's Project ID
     * @apiSuccess {DateTime}  ProjectUsersList.ProjectUser.created_at  Project-User's date time of create
     * @apiSuccess {DateTime}  ProjectUsersList.ProjectUser.updated_at  Project-User's date time of update
     *
     * @apiUse DefaultCreateErrorResponse
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
     * @api {post} /api/v1/projects-users/bulk-create BulkCreate
     * @apiParamExample {json} Simple-Request-Example:
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
     * @apiDescription Multiple Create Project Users relation
     * @apiVersion 0.1.0
     * @apiName BulkCreateProjectUsers
     * @apiGroup ProjectUsers
     *
     * @apiParam {Object[]} relations                   Project-User relations (Array of object)
     * @apiParam {Object}   relations.object            Object Project-User relation
     * @apiParam {Integer}  relations.object.project_id Project-User's Project ID
     * @apiParam {Integer}  relations.object.user_id    Project-User's User ID
     *
     * @apiSuccess {Object[]} messages                   Project-Users (Array of objects)
     * @apiSuccess {Object}   messages.object            Project-Users object
     * @apiSuccess {Integer}  messages.object.user_id    Project-User's User ID
     * @apiSuccess {Integer}  messages.object.project_id Project-User's Project ID
     * @apiSuccess {DateTime} messages.object.created_at Project-User's date time of create
     * @apiSuccess {DateTime} messages.object.updated_at Project-User's date time of update
     *
     * @apiUse DefaultBulkCreateErrorResponse
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkCreate(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.create'), $request->all());
        $result = [];

        if (empty($requestData['relations'])) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.error.item.bulkEdit'), [
                'error' => 'validation fail',
                'reason' => 'relations is empty'
            ]),
                400
            );
        }

        foreach ($requestData['relations'] as $relation) {
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

            $result[] = $item;
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                'messages' => $result,
            ])
        );
    }

    /**
     * @api {post} /api/v1/projects-users/destroy Destroy
     * @apiDescription Destroy Project Users relation
     * @apiParamExample {json} Simple-Request-Example:
     *  {
     *      "project_id":1,
     *      "user_id":4
     *  }
     * @apiVersion 0.1.0
     * @apiName DestroyProjectUsers
     * @apiGroup ProjectUsers
     *
     * @apiParam {Integer} project_id Project-User's Project ID
     * @apiParam {Integer} user_id    Project-User's User ID
     *
     * @apiSuccess {String} message Message about success item remove
     *
     * @apiError (Error 400) {String} error  Name of error
     * @apiError (Error 400) {String} reason Reason of error
     *
     * @param Request $request
     * @return JsonResponse
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
                ])
            );
        }

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.remove'), [
                'message' => 'Item has been removed'
            ])
        );
    }

    /**
     * @api {post} /api/v1/projects-users/bulk-destroy BulkDestroy
     * @apiParamExample {json} Simple-Request-Example:
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
     * @apiParam {Object[]} relations                   Project-User relations (Array of object)
     * @apiParam {Object}   relations.object            Object Project-User relation
     * @apiParam {Integer}  relations.object.project_id Project-User's Project ID
     * @apiParam {Integer}  relations.object.user_id    Project-User's User ID
     *
     * @apiSuccess {Object[]} messages        Messages (Array of objects)
     * @apiSuccess {Object}   messages.object Message about success item remove
     *
     * @apiUse DefaultBulkDestroyErrorResponse
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.destroy'), $request->all());
        $result = [];

        if (empty($requestData['relations'])) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.error.item.bulkEdit'), [
                'error' => 'validation fail',
                'reason' => 'relations is empty'
            ]),
                400
            );
        }

        foreach ($requestData['relations'] as $relation) {
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
                        'code' =>400
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