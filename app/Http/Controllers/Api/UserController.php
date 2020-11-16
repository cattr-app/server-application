<?php

namespace App\Http\Controllers\Api;

use App;
use Exception;
use Filter;
use App\Mail\UserCreated;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Event;
use Mail;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\EditUserRequest;
use App\Http\Requests\User\SendInviteUserRequest;
use App\Http\Requests\User\ShowUserRequest;
use App\Http\Requests\User\DestroyUserRequest;
use Illuminate\Support\Str;

class UserController extends ItemController
{
    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        return [];
    }

    /**
     * Get the model class.
     *
     * @return string
     */
    public function getItemClass(): string
    {
        return User::class;
    }

    /**
     * Get the event unique name part.
     *
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'user';
    }

    /**
     * @api             {get, post} /users/list List
     * @apiDescription  Get list of Users with any params
     *
     * @apiVersion      1.0.0
     * @apiName         GetUserList
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_list
     * @apiPermission   users_full_access
     *
     * @apiUse          UserParams
     * @apiUse          UserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  [
     *    {
     *      "id": 1,
     *      "full_name": "Admin",
     *      "email": "admin@example.com",
     *      "url": "",
     *      "company_id": 1,
     *      "avatar": "",
     *      "screenshots_active": 1,
     *      "manual_time": 0,
     *      "computer_time_popup": 300,
     *      "blur_screenshots": false,
     *      "web_and_app_monitoring": true,
     *      "screenshots_interval": 9,
     *      "active": 1,
     *      "deleted_at": null,
     *      "created_at": "2019-11-04T10:01:50+00:00",
     *      "updated_at": "2019-11-04T10:01:50+00:00",
     *      "timezone": null,
     *      "important": 0,
     *      "change_password": 0,
     *      "is_admin": 0,
     *      "role_id": 1
     *    },
     *    {
     *      "id": 2,
     *      "full_name": "Darwin",
     *      "email": "darwin@seleondar.ru",
     *      "url": null,
     *      "company_id": null,
     *      "avatar": null,
     *      "screenshots_active": 1,
     *      "manual_time": 1,
     *      "computer_time_popup": 5000,
     *      "blur_screenshots": null,
     *      "web_and_app_monitoring": null,
     *      "screenshots_interval": 5,
     *      "active": 1,
     *      "deleted_at": null,
     *      "created_at": "2019-11-04T10:22:20+00:00",
     *      "updated_at": "2019-11-06T10:42:25+00:00",
     *      "timezone": "Asia\/Omsk",
     *      "important": 0,
     *      "change_password": 0,
     *      "is_admin": 0,
     *      "role_id": 2
     *    }
     *  ]
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */
    public function index(Request $request): JsonResponse
    {
        return $this->_index($request);
    }

    /**
     * @api             {post} /users/create Create
     * @apiDescription  Create User Entity
     *
     * @apiVersion      1.0.0
     * @apiName         CreateUser
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_create
     * @apiPermission   users_full_access
     *
     * @apiParam {String}   email      New user email
     * @apiParam {String}   full_name  New user name
     * @apiParam {String}   password   New user password
     * @apiParam {Integer}  active     Will new user be active or not `(1 - active, 0 - not)`
     * @apiParam {Integer}  role_id    ID of the role of the new user
     *
     * @apiParamExample {json} Request Example
     * {
     *   "full_name": "John Doe",
     *   "email": "johndoe@example.com",
     *   "active": "1",
     *   "password": "secretpassword",
     *   "role_id": "3"
     * }
     *
     * @apiSuccess {Object}   res      User
     *
     * @apiUse UserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": {
     *      "full_name": "John Doe",
     *      "email": "johndoe@example.com",
     *      "active": "1",
     *      "role_id": "1",
     *      "updated_at": "2018-10-18 09:06:36",
     *      "created_at": "2018-10-18 09:06:36",
     *      "id": 3
     *    }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */
    /**
     * @param CreateUserRequest $request
     * @return JsonResponse
     */
    public function create(CreateUserRequest $request): JsonResponse
    {
        return $this->_create($request);
    }

    /**
     * @api             {post} /users/edit Edit
     * @apiDescription  Edit User
     *
     * @apiVersion      1.0.0
     * @apiName         EditUser
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_edit
     * @apiPermission   users_full_access
     *
     * @apiUse UserParams
     *
     * @apiParam {Integer}  id  ID of the target user
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1,
     *   "full_name": "Jonni Tree",
     *   "email": "gook@tree.com",
     *   "active": "1"
     * }
     *
     * @apiSuccess {Object}   res      User
     *
     * @apiUse UserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "res": {
     *      "id": 1,
     *      "full_name": "Jonni Tree",
     *       "email": "gook@tree.com",
     *       "url": "",
     *       "company_id": 1,
     *       "avatar": "",
     *       "screenshots_active": 1,
     *       "manual_time": 0,
     *       "computer_time_popup": 300,
     *       "blur_screenshots": 0,
     *       "web_and_app_monitoring": 1,
     *       "screenshots_interval": 9,
     *       "role": { "id": 2, "name": "user", "deleted_at": null,
     *                 "created_at": "2018-10-12 11:44:08", "updated_at": "2018-10-12 11:44:08" },
     *       "active": "1",
     *       "deleted_at": null,
     *       "created_at": "2018-10-18 09:36:22",
     *       "updated_at": "2018-10-18 11:04:50",
     *       "role_id": 1,
     *       "timezone": null,
     *       "user_language": "en"
     *      }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     */
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function edit(EditUserRequest $request): JsonResponse
    {
        $requestData = $request->validated();

        $requestData = Filter::process(
            $this->getEventUniqueName('request.item.edit'),
            $requestData
        );

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(),
                ['id' => $requestData['id']]
            )
        );
        /** @var Model $item */
        $item = $itemsQuery->first();

        if (!$item) {
            return new JsonResponse(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'error_type' => 'query.item_not_found',
                    'message' => 'User not found',
                ]),
                404
            );
        }

        if (App::environment('demo')) {
            unset($requestData['password']);
        }

        $item = Filter::process($this->getEventUniqueName('item.edit'), $item);

        $item->update($requestData);

        Event::dispatch($this->getEventUniqueName('item.edit.after'), [$item, $requestData]);

        return new JsonResponse(
            Filter::process($this->getEventUniqueName('answer.success.item.edit'), [
                'res' => $item,
            ])
        );
    }

    /**
     * @api             {get, post} /users/show Show
     * @apiDescription  Show User
     *
     * @apiVersion      1.0.0
     * @apiName         ShowUser
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_show
     * @apiPermission   users_full_access
     *
     * @apiParam {Integer} id   User id
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiUse UserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "id": 1,
     *    "full_name": "Admin",
     *    "email": "admin@example.com",
     *    "url": "",
     *    "company_id": 1,
     *    "avatar": "",
     *    "screenshots_active": 1,
     *    "manual_time": 0,
     *    "computer_time_popup": 300,
     *    "blur_screenshots": 0,
     *    "role": { "id": 2, "name": "user", "deleted_at": null,
     *              "created_at": "2018-10-12 11:44:08", "updated_at": "2018-10-12 11:44:08" },
     *    "web_and_app_monitoring": 1,
     *    "screenshots_interval": 9,
     *    "active": 1,
     *    "deleted_at": null,
     *    "created_at": "2018-10-18 09:36:22",
     *    "updated_at": "2018-10-18 09:36:22",
     *    "role_id": 1,
     *    "timezone": null,
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     * @apiUse         ForbiddenError
     * @apiUse         ValidationError
     */

    /**
     * @param ShowUserRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function show(ShowUserRequest $request): JsonResponse
    {
        return $this->_show($request);
    }

    /**
     * @api             {post} /users/remove Destroy
     * @apiDescription  Destroy User
     *
     * @apiVersion      1.0.0
     * @apiName         DestroyUser
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_remove
     * @apiPermission   users_full_access
     *
     * @apiParam {Integer}  id  ID of the target user
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiSuccess {String}   message  Destroy status
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "message": "Item has been removed"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */
    public function destroy(DestroyUserRequest $request): JsonResponse
    {
        return $this->_destroy($request);
    }

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /users/bulk-edit Bulk Edit
     * @apiDescription  Editing Multiple Users
     *
     * @apiVersion      1.0.0
     * @apiName         bulkEditUsers
     * @apiGroup        User
     *
     * @apiPermission   users_bulk_edit
     * @apiPermission   users_full_access
     */

    /**
     * @api             {get,post} /users/count Count
     * @apiDescription  Count Users
     *
     * @apiVersion      1.0.0
     * @apiName         Count
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_count
     * @apiPermission   users_full_access
     *
     * @apiSuccess {String}   total    Amount of users that we have
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "total": 2
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */
    public function count(Request $request): JsonResponse
    {
        return $this->_count($request);
    }

    /**
     * @apiDeprecated   since 1.0.0 use now (#Project_Users:List)
     * @api             {post} /users/relations Relations
     * @apiDescription  Show attached users and to whom the user is attached
     *
     * @apiVersion      1.0.0
     * @apiName         RelationsUser
     * @apiGroup        User
     *
     * @apiPermission   users_relations
     */

    /**
     * TODO: apidoc
     *
     * @param SendInviteUserRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function sendInvite(SendInviteUserRequest $request)
    {
        $requestData = $request->validated();

        $itemsQuery = $this->applyQueryFilter($this->getQuery(), ['id' => $requestData['id']]);
        $item = $itemsQuery->first();
        if (!$item) {
            return new JsonResponse(
                [
                    'error_type' => 'query.item_not_found',
                    'message' => 'Item not found'
                ],
                404
            );
        }

        $password = Str::random(16);
        $item->password = $password;
        $item->invitation_sent = true;
        $item->save();

        Mail::to($item->email)->send(new UserCreated($item->email, $password));

        return new JsonResponse([
            'res' => $item,
        ]);
    }

    /**
     * @param bool $withRelations
     * @param bool $withSoftDeleted
     * @return Builder
     */
    public function getQuery($withRelations = true, $withSoftDeleted = false): Builder
    {
        $query = parent::getQuery($withRelations, $withSoftDeleted);

        if (request('global_scope')) {
            request()->request->remove('global_scope');

            if (auth()->user()->hasProjectRole('manager')) {
                $query->withoutGlobalScope(App\Scopes\UserScope::class);
            }
        }

        return $query;
    }
}
