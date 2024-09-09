<?php

namespace App\Http\Controllers\Api;

use App;
use App\Enums\Role;
use App\Enums\ScreenshotsState;
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
use App\Models\Setting;
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
     * @apiVersion      4.0.0
     * @apiName         GetUserList
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_list
     * @apiPermission   users_full_access
     *
     * @apiSuccess {Object[]} users                         List of users.
     * @apiSuccess {Integer}  users.id                      The unique ID of the user.
     * @apiSuccess {String}   users.full_name               Full name of the user.
     * @apiSuccess {String}   users.email                   Email address of the user.
     * @apiSuccess {String}   users.url                     URL associated with the user.
     * @apiSuccess {Integer}  users.company_id              ID of the company the user belongs to.
     * @apiSuccess {String}   users.avatar                  URL of the user's avatar image.
     * @apiSuccess {Integer}  users.screenshots_state       The current state of screenshot monitoring.
     * @apiSuccess {Boolean}  users.manual_time             Indicates if manual time tracking is allowed.
     * @apiSuccess {Integer}  users.computer_time_popup     Time in seconds before showing a time popup.
     * @apiSuccess {Boolean}  users.blur_screenshots        Indicates if screenshots are blurred.
     * @apiSuccess {Boolean}  users.web_and_app_monitoring  Indicates if web and app monitoring is enabled.
     * @apiSuccess {Integer}  users.screenshots_interval    Interval in minutes for taking screenshots.
     * @apiSuccess {Boolean}  users.active                  Indicates if the user is active.
     * @apiSuccess {String}   users.deleted_at              Deletion timestamp, or `null` if the user is not deleted.
     * @apiSuccess {String}   users.created_at              Creation timestamp of the user.
     * @apiSuccess {String}   users.updated_at  Last update timestamp of the user.
     * @apiSuccess {String}   users.timezone  The timezone of the user, or `null`.
     * @apiSuccess {Boolean}  users.important  Indicates if the user is marked as important.
     * @apiSuccess {Boolean}  users.change_password  Indicates if the user must change their password.
     * @apiSuccess {Integer}  users.role_id  ID of the user's role.
     * @apiSuccess {String}   users.user_language  Language preference of the user.
     * @apiSuccess {String}   users.type  The user type, e.g., "employee".
     * @apiSuccess {Boolean}  users.invitation_sent  Indicates if an invitation has been sent.
     * @apiSuccess {Integer}  users.nonce  Nonce value for secure actions.
     * @apiSuccess {Boolean}  users.client_installed  Indicates if the client software is installed.
     * @apiSuccess {Boolean}  users.permanent_screenshots  Indicates if permanent screenshots are enabled.
     * @apiSuccess {String}   users.last_activity  The last recorded activity timestamp.
     * @apiSuccess {Boolean}  users.screenshots_state_locked  Indicates if screenshot state is locked.
     * @apiSuccess {Boolean}  users.online  Indicates if the user is currently online.
     * @apiSuccess {Boolean}  users.can_view_team_tab  Indicates if the user can view the team tab.
     * @apiSuccess {Boolean}  users.can_create_task  Indicates if the user can create tasks.
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     * {
     *  [
     *    {
     *       "id": 1,
     *       "full_name": "Admin",
     *       "email": "admin@cattr.app",
     *       "url": "",
     *       "company_id": 1,
     *       "avatar": "",
     *       "screenshots_state": 1,
     *       "manual_time": 0,
     *       "computer_time_popup": 300,
     *       "blur_screenshots": false,
     *       "web_and_app_monitoring": true,
     *       "screenshots_interval": 5,
     *       "active": 1,
     *       "deleted_at": null,
     *       "created_at": "2023-10-26T10:26:17.000000Z",
     *       "updated_at": "2024-08-19T10:42:18.000000Z",
     *       "timezone": null,
     *       "important": 0,
     *       "change_password": 0,
     *       "role_id": 0,
     *       "user_language": "en",
     *       "type": "employee",
     *       "invitation_sent": false,
     *       "nonce": 0,
     *       "client_installed": 0,
     *       "permanent_screenshots": 0,
     *       "last_activity": "2024-08-19 10:42:18",
     *       "screenshots_state_locked": false,
     *       "online": false,
     *       "can_view_team_tab": true,
     *       "can_create_task": true
     *   },
     *   {
     *       "id": 2,
     *       "full_name": "Fabiola Mertz",
     *       "email": "projectManager@example.com",
     *       "url": "",
     *       "company_id": 1,
     *       "avatar": "",
     *       "screenshots_state": 2,
     *       "manual_time": 0,
     *       "computer_time_popup": 300,
     *       "blur_screenshots": false,
     *       "web_and_app_monitoring": true,
     *       "screenshots_interval": 5,
     *       "active": 1,
     *       "deleted_at": null,
     *       "created_at": "2023-10-26T10:26:17.000000Z",
     *       "updated_at": "2023-10-26T10:26:17.000000Z",
     *       "timezone": null,
     *       "important": 0,
     *       "change_password": 0,
     *       "role_id": 2,
     *       "user_language": "en",
     *       "type": "employee",
     *       "invitation_sent": false,
     *       "nonce": 0,
     *       "client_installed": 0,
     *       "permanent_screenshots": 0,
     *       "last_activity": "2023-10-26 09:44:17",
     *       "screenshots_state_locked": false,
     *       "online": false,
     *       "can_view_team_tab": false,
     *       "can_create_task": false
     *   },...
     *  ]
     * }
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
     * @apiVersion      4.0.0
     * @apiName         CreateUser
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_create
     * @apiPermission   users_full_access
     *
     * @apiParam {String}   user_language         The language of the new user (e.g., "en")
     * @apiParam {String}   timezone              The timezone of the new user (e.g., "Europe/Moscow")
     * @apiParam {Integer}  role_id               ID of the role of the new user
     * @apiParam {Integer}  active                Will new user be active or not `(1 - active, 0 - not)`
     * @apiParam {Integer}  screenshots_state     State of screenshots monitoring (e.g., 1 for enabled)
     * @apiParam {Boolean}  send_invite           Whether to send an invitation to the new user (true - send, false - do not send)
     * @apiParam {Boolean}  manual_time           Whether manual time tracking is enabled for the new user
     * @apiParam {Integer}  screenshots_interval  Interval in minutes for taking screenshots
     * @apiParam {Integer}  computer_time_popup   Time in minutes before showing a time popup
     * @apiParam {String}   type                  The type of user (e.g., "employee")
     * @apiParam {Boolean}  web_and_app_monitoring Whether web and app monitoring is enabled
     * @apiParam {String}   email                 New user email
     * @apiParam {String}   password              New user password
     * @apiParam {String}   full_name             New user name
     * @apiParamExample {json} Request Example
     * {
     *   "user_language" : "en",
     *   "timezone" : "Europe/Moscow",
     *   "role_id" : 2,
     *   "active" : true,
     *   "screenshots_state" : 1,
     *   "send_invite" : 1,
     *   "manual_time" : 1,
     *   "screenshots_interval" : 10,
     *   "computer_time_popup" : 3,
     *   "type" : "employee",
     *   "web_and_app_monitoring" : 1,
     *   "email" : "123@cattr.app",
     *   "password" : "password",
     *   "full_name" : "name"
     * }
     * @apiSuccess {String}   full_name                Full name of the user.
     * @apiSuccess {String}   email                    Email address of the user.
     * @apiSuccess {String}   user_language            Language of the user.
     * @apiSuccess {Boolean}  active                   Whether the user is active.
     * @apiSuccess {Integer}  screenshots_state        State of screenshots monitoring.
     * @apiSuccess {Boolean}  manual_time              Whether manual time tracking is enabled.
     * @apiSuccess {Integer}  screenshots_interval     Interval in minutes for taking screenshots.
     * @apiSuccess {Integer}  computer_time_popup      Time in minutes before showing a time popup.
     * @apiSuccess {String}   timezone                 Timezone of the user.
     * @apiSuccess {Integer}  role_id                  ID of the role assigned to the user.
     * @apiSuccess {String}   type                     Type of the user (e.g., "employee").
     * @apiSuccess {Boolean}  web_and_app_monitoring   Whether web and app monitoring is enabled.
     * @apiSuccess {Boolean}  screenshots_state_locked Whether the screenshot state is locked.
     * @apiSuccess {Boolean}  invitation_sent          Whether an invitation has been sent.
     * @apiSuccess {String}   updated_at               Timestamp of the last update.
     * @apiSuccess {String}   created_at               Timestamp of when the user was created.
     * @apiSuccess {Integer}  id                       ID of the created user.
     * @apiSuccess {Boolean}  online                   Whether the user is currently online.
     * @apiSuccess {Boolean}  can_view_team_tab        Whether the user can view the team tab.
     * @apiSuccess {Boolean}  can_create_task          Whether the user can create tasks.
     *
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *   "full_name": "name",
     *   "email": "123@cattr.app",
     *   "user_language": "en",
     *   "active": 1,
     *   "screenshots_state": 1,
     *   "manual_time": 1,
     *   "screenshots_interval": 10,
     *   "computer_time_popup": 3,
     *   "timezone": "Europe/Moscow",
     *   "role_id": 2,
     *   "type": "employee",
     *   "web_and_app_monitoring": true,
     *   "screenshots_state_locked": true,
     *   "invitation_sent": true,
     *   "updated_at": "2024-08-21T14:29:06.000000Z",
     *   "created_at": "2024-08-21T14:29:06.000000Z",
     *   "id": 10,
     *   "online": false,
     *   "can_view_team_tab": false,
     *   "can_create_task": false
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
        Filter::listen(Filter::getRequestFilterName(), static function ($requestData) use ($request) {
            $requestData['screenshots_state_locked'] = $request->user()->isAdmin() && ScreenshotsState::tryFrom($requestData['screenshots_state'])->mustBeInherited();

            return $requestData;
        });

        return $this->_create($request);
    }

    /**
     * @api             {post} /users/edit Edit
     * @apiDescription  Edit User
     *
     * @apiVersion      4.0.0
     * @apiName         EditUser
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_edit
     * @apiPermission   users_full_access
     * @apiParam {String}   user_language          The language of the new user (e.g., "en")
     * @apiParam {String}   timezone               The timezone of the new user (e.g., "Europe/Moscow")
     * @apiParam {Integer}  role_id                ID of the role of the new user
     * @apiParam {Integer}  id                     The ID of the user being edited.
     * @apiParam {String}   full_name              New user name
     * @apiParam {String}   email                  New user email
     * @apiParam {String}   url                    URL associated with the user
     * @apiParam {Integer}  company_id             The ID of the company to which the user belongs
     * @apiParam {String}   avatar                 The URL of the user’s avatar
     * @apiParam {Integer}  screenshots_state      State of screenshots monitoring (e.g., 1 for enabled)
     * @apiParam {Boolean}  manual_time            Whether manual time tracking is enabled for the new user
     * @apiParam {Integer}  computer_time_popup    Time in minutes before showing a time popup
     * @apiParam {Boolean}  blur_screenshots       Indicates if screenshots are blurred
     * @apiParam {Boolean}  web_and_app_monitoring Whether web and app monitoring is enabled
     * @apiParam {Integer}  screenshots_interval   Interval in minutes for taking screenshots
     * @apiParam {Integer}  active                 Will new user be active or not `(1 - active, 0 - not)`
     * @apiParam {String}   deleted_at             Deletion timestamp, or `null` if the user is not deleted.
     * @apiParam {Boolean}  send_invite           Whether to send an invitation to the new user (true - send, false - do not send)
     *
     *
     *
     * @apiParam {String}   type                  The type of user (e.g., "employee")
     *
     * @apiParam {String}   password              New user password
     *
     *
     * @apiParamExample {json} Request Example
     * {
     *       "user_language" : "en",
     *       "timezone" : "Europe/Moscow",
     *       "role_id" : 2,
     *       "id" : 3,
     *       "full_name" : "Rachael Reichert",
     *       "email": "projectAuditor@example.com",
     *       "url" : null,
     *       "company_id" : 1,
     *       "avatar" : null,
     *       "screenshots_state" : 1,
     *       "manual_time" : 0,
     *       "computer_time_popup" : 300,
     *       "blur_screenshots" : false,
     *       "web_and_app_monitoring" : true,
     *       "screenshots_interval" : 5,
     *       "active" : true,
     *       "deleted_at" : null,
     *       "created_at" : "2023-10-26T10:26:42.000000Z",
     *       "updated_at" : "2023-10-26T10:26:42.000000Z",
     *       "important" : 0,
     *       "change_password" : 0,
     *       "type" : "employee",
     *       "invitation_sent" : false,
     *       "nonce" : 0,
     *       "client_installed" : 0,
     *       "permanent_screenshots" : 0,
     *       "last_activity" : "2023-10-26 10:05:42",
     *       "screenshots_state_locked" : false,
     *       "online" : false,
     *       "can_view_team_tab" : false,
     *       "can_create_task" : false
     *       }
     * @apiUse         UserObject
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
        Filter::listen(Filter::getActionFilterName(), static function (User $user) use ($request) {
            if ($user->screenshots_state_locked && !$request->user()->isAdmin()) {
                $user->screenshots_state = $user->getOriginal('screenshots_state');
                return $user;
            }

            $user->screenshots_state_locked = $request->user()->isAdmin() && ScreenshotsState::tryFrom($user->screenshots_state)->mustBeInherited();

            return $user;
        });

        return $this->_edit($request);
    }

    /**
     * @api             {get, post} /users/show Show User
     * @apiDescription  Retrieves detailed information about a specific user.
     *
     * @apiVersion      4.0.0
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
     * @apiParamExample {json} Request Example:
     * {
     *   "id": 1
     * }
     * @apiUse UserObject
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
     * @apiVersion      4.0.0
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
     *  HTTP/1.1 204 No Content
     *  {
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
     * @throws Exception
     * @api             {get,post} /users/count Count
     * @apiDescription  Count Users
     *
     * @apiVersion      4.0.0
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
     * @param SendInviteUserRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    /**
     * @api             {post} /api/users/send-invite Send User Invitation
     * @apiDescription  Sends an invitation to a user by generating a password, marking the invitation as sent, and dispatching relevant events.
     *
     * @apiVersion      4.0.0
     * @apiName         SendUserInvite
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_invite
     *
     * @apiParam {Integer} id   The ID of the user to whom the invitation will be sent.
     *
     * @apiParamExample {json} Request Example:
     * {
     *   "id": 1
     * }
     *
     * @apiSuccess {String} message  A confirmation that the invite was sent successfully.
     *
     * @apiSuccessExample {json} Success Response:
     *  HTTP/1.1 204 No Content
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
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
     * @api             {patch} /users/activity Activity
     * @apiDescription  Updates the time of the user's last activity
     *
     * @apiVersion      4.0.0
     * @apiName         Activity
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 204 No Content
     *  {
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
