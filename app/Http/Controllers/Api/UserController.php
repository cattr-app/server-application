<?php

namespace App\Http\Controllers\Api;

use App;
use App\Http\Requests\User\ListUsersRequest;
use App\Scopes\UserAccessScope;
use Settings;
use Carbon\Carbon;
use Exception;
use Filter;
use App\Mail\UserCreated;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use CatEvent;
use Mail;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\EditUserRequest;
use App\Http\Requests\User\SendInviteUserRequest;
use App\Http\Requests\User\ShowUserRequest;
use App\Http\Requests\User\DestroyUserRequest;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class UserController extends ItemController
{
    protected const MODEL = User::class;

    /**
     * @throws Exception
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
     *      "role_id": 2
     *    }
     *  ]
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */
    public function index(ListUsersRequest $request): JsonResponse
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
     * @throws Throwable
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
     * @param EditUserRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function edit(EditUserRequest $request): JsonResponse
    {
        return $this->_edit($request);
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
     * @throws Throwable
     */
    public function show(ShowUserRequest $request): JsonResponse
    {
        return $this->_show($request);
    }

    /**
     * @throws Throwable
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
     * @throws Exception
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
    public function count(ListUsersRequest $request): JsonResponse
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
     * @throws Throwable
     */
    public function sendInvite(SendInviteUserRequest $request): JsonResponse
    {
        $requestId = Filter::process(Filter::getRequestFilterName(), $request->validated('id'));

        $itemsQuery = $this->getQuery(['id' => $requestId]);

        CatEvent::dispatch(Filter::getBeforeActionEventName(), $requestId);

        $item = Filter::process(Filter::getActionFilterName(), $itemsQuery->first());

        $password = Str::random();
        $item->password = $password;
        $item->invitation_sent = true;
        $item->save();

        throw_unless($item, new NotFoundHttpException);

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$requestId, $item]);

        $language = Settings::scope('core')->get('language', 'en');

        Mail::to($item->email)->locale($language)->send(new UserCreated($item->email, $password));

        return responder()->success()->respond(204);
    }

    /**
     * @api             {patch} /v1/users/activity Activity
     * @apiDescription  Updates the time of the user's last activity
     *
     * @apiVersion      1.0.0
     * @apiName         Activity
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *  }
     *
     * @apiUse          UnauthorizedError
     */
    public function updateActivity(): JsonResponse
    {
        $user = request()->user();

        CatEvent::dispatch(Filter::getBeforeActionEventName(), $user);

        Filter::process(Filter::getActionFilterName(), $user)->update(['last_activity' => Carbon::now()]);

        CatEvent::dispatch(Filter::getAfterActionEventName(), $user);

        return responder()->success()->respond(204);
    }
}
