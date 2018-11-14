<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\RelationsUsers;
use Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Validator;

class RelationsUsersController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return RelationsUsers::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'user_id'          => 'required|exists:users,id',
            'attached_user_id' => 'required|exists:users,id',
        ];
    }

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'attached-users';
    }

    /**
     * @api {get} /api/v1/attached-users/list?attached_user_id=:attached_user_id&user_id=:user_id List
     * @apiDescription Get list of Attached Users relations
     * @apiVersion 0.1.0
     * @apiName GetAttachedUsersList
     * @apiGroup AttachedUsers
     *
     * @apiParam {Integer} [attached_user_id] `QueryParam` Attached user id
     * @apiParam {Integer} [user_id]          `QueryParam` User id
     *
     * @apiSuccess (200) {Object[]} AttachedUsers AttachedUsers entities
     *
     * @apiSuccessExample {json} Success Response Example
     * [
     *    {
     *      "user_id": 1,
     *      "attached_user_id": 1,
     *      "created_at": "2018-09-28 13:53:57",
     *      "updated_at": "2018-09-28 13:53:59"
     *    }
     * ]
     *
     * @param Request $request
     *
     * @return JsonResponse
     */

    /**
     * @api {post} /api/v1/attached-users/create Create
     *
     * @apiDescription Create Attached Users relation
     * @apiVersion 0.1.0
     * @apiName CreateAttachedUsers
     * @apiGroup AttachedUsers
     *
     * @apiParam user_id Integer User id
     * @apiParam attached_user_id Attached to User
     *
     * @apiParamExample {json} Request Example
     * {
     *   "user_id": 1,
     *   "attached_user_id": 1
     * }
     *
     * @apiSuccess (200) {Integer} user_id              User id
     * @apiSuccess (200) {Integer} attached_user_id     Attached to User id
     * @apiSuccess (200) {String}  updated_at           DateTime of AttachedUser entity last update
     * @apiSuccess (200) {String}  created_at           DateTime of AttachedUser entity creation
     *
     *
     * @apiSuccessAnswer {json} Success-Response:
     *   [
     *     {
     *       "user_id": 1,
     *       "attached_user_id": 1,
     *       "updated_at": "2018-10-01 08:41:37",
     *       "created_at": "2018-10-01 08:41:37"
     *     }
     *   ]
     *
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request) : JsonResponse
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
     * @api {post} /api/v1/attached-users/bulk-create BulkCreate
     * @apiDescription Multiple Create Attached Users relation
     * @apiVersion 0.1.0
     * @apiName BulkCreateAttachedUsers
     * @apiGroup AttachedUsers
     *
     * @apiParam {Object[]}    array                         Relations
     * @apiParam {Object}      array.object                  Object Attached User relation
     * @apiParam {Integer}     array.object.attached_user_id Attached User id
     * @apiParam {Integer}     array.object.user_id          User id
     *
     * @apiParamExample {json} Request Example
     * {
     *   "relations": [
     *     {
     *       "user_id": "1",
     *       "attached_user_id": "1"
     *     }
     *   ]
     * }
     *
     * @apiSuccess {Object[]} messages                     AttachedUser entities
     * @apiSuccess {Integer}  messages.user_id             User id
     * @apiSuccess {String}   messages.attached_user_id    Attached User id
     * @apiSuccess {String}   messages.updated_at          Last relation update
     * @apiSuccess {String}   messages.created_at          When relation was created
     *
     * @apiSuccessExample {json} Response Example
     * {
     *   "messages": [
     *     {
     *       "user_id": "1",
     *       "attached_user_id": "1",
     *       "updated_at": "2018-10-22 06:56:23",
     *       "created_at": "2018-10-22 06:56:23"
     *     }
     *   ]
     * }
     *
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkCreate(Request $request) : JsonResponse
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
            'attached_user_id',
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
     * @api {delete} /api/v1/attached-users/remove Destroy
     * @apiDescription Destroy Attached Users relation
     * @apiVersion 0.1.0
     * @apiName DestroyAttachedUsers
     * @apiGroup AttachedUsers
     *
     * @apiParam {Integer} user_id          User id
     * @apiParam {Integer} attached_user_id Relation User id
     *
     * @apiParamExample {json} Request Example
     * {
     *   "user_id": 1,
     *   "attached_user_id": 1
     * }
     *
     * @apiSuccessParam {String} message Action status
     *
     * @apiSuccessExample {json} Response Example
     * {
     *   "message": "Item has been removed"
     * }
     *
     * @apiErrorParam (400) {String}  error   Error title
     * @apiErrorParam (400) {String}  reason  Error reason
     *
     * @apiErrorExample (400) {json} Error Response Example
     * {
     *   "error": "Item has not been removed",
     *   "reason": "Item not found"
     * }
     *
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(Request $request) : JsonResponse
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
                $this->getQuery(),
                $requestData
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
     * @api {delete, post} /api/v1/attached-users/bulk-remove BulkDestroy
     * @apiDescription Multiple Destroy Attached Users relation
     * @apiVersion 0.1.0
     * @apiName BulkDestroyAttachedUsers
     * @apiGroup AttachedUsers
     *
     * @apiParam {Object[]}    array                         AttachedUsers
     * @apiParam {Object}      array.object                  Project User
     * @apiParam {Integer}     array.object.attached_user_id Attached User id
     * @apiParam {Integer}     array.object.user_id          User id
     *
     * @apiParamExample {json} Request Example
     * {
     *   "relations": [
     *     {
     *       "user_id": "1",
     *       "attached_user_id": "1"
     *     }
     *   ]
     * }
     *
     * @apiSuccess {Object[]} messages               Messages
     * @apiSuccess {Object}   message                Message
     * @apiSuccess {String}   message.message        Status
     *
     * @apiSuccessExample {json} Response Example
     * {
     *   "messages": [
     *     {
     *       "message": "Item has been removed"
     *     }
     *   ]
     * }
     *
     * @apiError (404)  {Object[]} messages                 Messages
     * @apiError (404)  {Object}   messages.message         Message
     * @apiError (404)  {String}   messages.message.error   Error title
     * @apiError (404)  {String}   messages.message.reason  Error reason
     *
     * @apiErrorExample (404) {json} Errors Response Example
     * {
     *   "messages": [
     *     {
     *       "error": "Item has not been removed",
     *       "reason": "Item not found"
     *     }
     *   ]
     * }
     *
     * @apiUse UnauthorizedError
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function bulkDestroy(Request $request) : JsonResponse
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
                    $this->getQuery(),
                    $relation
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
                    'code' => 400
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
