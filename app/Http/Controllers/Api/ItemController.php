<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\CattrFormRequest;
use Filter;
use App\Helpers\QueryHelper;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use CatEvent;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

abstract class ItemController extends Controller
{
    protected const MODEL = Model::class;

    /**
     * @apiDefine ItemNotFoundError
     * @apiErrorExample {json} No such item
     *  HTTP/1.1 404 Not Found
     *  {
     *    "message": "Item not found",
     *    "error_type": "query.item_not_found"
     *  }
     *
     * @apiVersion 1.0.0
     */

    /**
     * @apiDefine ValidationError
     * @apiErrorExample {json} Validation error
     *  HTTP/1.1 400 Bad Request
     *  {
     *    "message": "Validation error",
     *    "error_type": "validation",
     *    "info": "Invalid id"
     *  }
     *
     * @apiError (Error 400) {String}  info  Validation errors
     *
     * @apiVersion 1.0.0
     */
    /**
     * @apiDefine ProjectIDParam
     * @apiParam {Integer} id  Project ID
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "id": 1
     *  }
     */
    /**
     * @apiDefine ProjectObject
     *
     * @apiSuccess {Integer}  id                    Project ID
     * @apiSuccess {Integer}  company_id            Company ID
     * @apiSuccess {String}   name                  Project name
     * @apiSuccess {String}   description           Project description
     * @apiSuccess {String}   deleted_at            Project deletion date or null
     * @apiSuccess {String}   created_at            Project creation date
     * @apiSuccess {String}   updated_at            Project update date
     * @apiSuccess {Boolean}  important             Project importance
     * @apiSuccess {String}   source                Project source
     * @apiSuccess {Integer}  default_priority_id   Default priority ID or null
     * @apiSuccess {Object[]} phases                List of project phases
     * @apiSuccess {Integer}  phases.id             Phase ID
     * @apiSuccess {String}   phases.name           Phase name
     * @apiSuccess {String}   phases.description    Phase description
     * @apiSuccess {Integer}  phases.tasks_count    Number of tasks in the phase
     * @apiSuccess {String}   phases.created_at     Phase creation date
     * @apiSuccess {String}   phases.updated_at     Phase update date
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *      "id": 1,
     *      "company_id": 1,
     *      "name": "Dolores voluptates.",
     *      "description": "Deleniti maxime fugit nesciunt. Ut maiores deleniti tempora vel. Nisi aut doloremque accusantium tempore aut.",
     *      "deleted_at": null,
     *      "created_at": "2023-10-26T10:26:17.000000Z",
     *      "updated_at": "2023-10-26T10:26:17.000000Z",
     *      "important": 1,
     *      "source": "internal",
     *      "default_priority_id": null,
     *      "phases": []
     *  }
     */
    /**
     * @apiDefine UserObject
     * @apiSuccess {Integer}   id                         User ID.
     * @apiSuccess {String}    full_name                  Full name of the user.
     * @apiSuccess {String}    email                      Email address of the user.
     * @apiSuccess {String}    url                        URL associated with the user (if any).
     * @apiSuccess {Integer}   company_id                 ID of the company the user belongs to.
     * @apiSuccess {String}    avatar                     URL of the user's avatar (if any).
     * @apiSuccess {Boolean}   screenshots_state          State of screenshot capturing.
     * @apiSuccess {Boolean}   manual_time                Indicates whether manual time tracking is enabled.
     * @apiSuccess {Integer}   computer_time_popup        Time in seconds for the computer time popup.
     * @apiSuccess {Boolean}   blur_screenshots           Indicates if screenshots are blurred.
     * @apiSuccess {Boolean}   web_and_app_monitoring     Indicates if web and app monitoring is enabled.
     * @apiSuccess {Integer}   screenshots_interval       Interval for capturing screenshots in minutes.
     * @apiSuccess {Boolean}   active                     Indicates if the user account is active.
     * @apiSuccess {String}    created_at                 Timestamp of when the user was created.
     * @apiSuccess {String}    updated_at                 Timestamp of when the user was last updated.
     * @apiSuccess {String}    deleted_at                 Timestamp of when the user was deleted (if applicable).
     * @apiSuccess {String}    timezone                   User's timezone.
     * @apiSuccess {Boolean}   important                  Indicates if the user is marked as important.
     * @apiSuccess {Boolean}   change_password            Indicates if the user is required to change their password.
     * @apiSuccess {Integer}   role_id                    Role ID associated with the user.
     * @apiSuccess {String}    user_language              Language preference of the user.
     * @apiSuccess {String}    type                       User type (e.g., employee, admin).
     * @apiSuccess {Boolean}   invitation_sent            Indicates if an invitation has been sent to the user.
     * @apiSuccess {Boolean}   client_installed           Indicates if the tracking client is installed.
     * @apiSuccess {Boolean}   permanent_screenshots      Indicates if permanent screenshots are enabled.
     * @apiSuccess {String}    last_activity              Timestamp of the user's last activity.
     * @apiSuccess {Boolean}   screenshots_state_locked   Indicates if the screenshot state is locked.
     * @apiSuccess {Boolean}   online                     Indicates if the user is currently online.
     * @apiSuccess {Boolean}   can_view_team_tab          Indicates if the user can view the team tab.
     * @apiSuccess {Boolean}   can_create_task            Indicates if the user can create tasks.
     *
     * @apiSuccessExample {json} Response Example:
     *  HTTP/1.1 200 OK
     *  {
     *      "id": 1,
     *      "full_name": "Admin",
     *      "email": "admin@cattr.app",
     *      "url": "",
     *      "company_id": 1,
     *      "avatar": "",
     *      "screenshots_state": 1,
     *      "manual_time": 0,
     *      "computer_time_popup": 300,
     *      "blur_screenshots": false,
     *      "web_and_app_monitoring": true,
     *      "screenshots_interval": 5,
     *      "active": 1,
     *      "deleted_at": null,
     *      "created_at": "2023-10-26T10:26:17.000000Z",
     *      "updated_at": "2024-08-20T09:22:02.000000Z",
     *      "timezone": null,
     *      "important": 0,
     *      "change_password": 0,
     *      "role_id": 0,
     *      "user_language": "en",
     *      "type": "employee",
     *      "invitation_sent": false,
     *      "nonce": 0,
     *      "client_installed": 0,
     *      "permanent_screenshots": 0,
     *      "last_activity": "2024-08-20 09:22:02",
     *      "screenshots_state_locked": false,
     *      "online": false,
     *      "can_view_team_tab": true,
     *      "can_create_task": true
     *  }
     */

    /**
     * @apiDefine UserParams
     */
    /**
     * @apiDefine User
     * @apiSuccess {String}   access_token          Token
     * @apiSuccess {String}   token_type            Token Type
     * @apiSuccess {String}   expires_in            Token TTL (ISO 8601 Date)
     * @apiSuccess {Object}   user                  User Entity
     * @apiSuccess {Integer}  user.id               ID of the user
     * @apiSuccess {String}   user.full_name        Full name of the user
     * @apiSuccess {String}   user.email            Email of the user
     * @apiSuccess {String}   [user.url]            URL of the user (optional)
     * @apiSuccess {Integer}  user.company_id       Company ID of the user
     * @apiSuccess {String}   [user.avatar]         Avatar URL of the user (optional)
     * @apiSuccess {Boolean}  user.screenshots_active Indicates if screenshots are active
     * @apiSuccess {Boolean}  user.manual_time      Indicates if manual time tracking is allowed
     * @apiSuccess {Integer}  user.computer_time_popup Time interval for computer time popup
     * @apiSuccess {Boolean}  user.blur_screenshots Indicates if screenshots are blurred
     * @apiSuccess {Boolean}  user.web_and_app_monitoring Indicates if web and app monitoring is enabled
     * @apiSuccess {Integer}  user.screenshots_interval Interval for taking screenshots
     * @apiSuccess {Boolean}  user.active           Indicates if the user is active
     * @apiSuccess {String}   [user.deleted_at]     Deletion timestamp (if applicable, otherwise null)
     * @apiSuccess {String}   user.created_at       Creation timestamp
     * @apiSuccess {String}   user.updated_at       Last update timestamp
     * @apiSuccess {String}   [user.timezone]       Timezone of the user (optional)
     * @apiSuccess {Boolean}  user.important        Indicates if the user is marked as important
     * @apiSuccess {Boolean}  user.change_password  Indicates if the user needs to change password
     * @apiSuccess {Integer}  user.role_id          Role ID of the user
     * @apiSuccess {String}   user.user_language    Language of the user
     * @apiSuccess {String}   user.type             Type of the user (e.g., "employee")
     * @apiSuccess {Boolean}  user.invitation_sent  Indicates if invitation is sent to the user
     * @apiSuccess {Integer}  user.nonce            Nonce value of the user
     * @apiSuccess {Boolean}  user.client_installed Indicates if client is installed
     * @apiSuccess {Boolean}  user.permanent_screenshots Indicates if screenshots are permanent
     * @apiSuccess {String}   user.last_activity    Last activity timestamp of the user
     * @apiSuccess {Boolean}  user.online           Indicates if the user is online
     * @apiSuccess {Boolean}  user.can_view_team_tab Indicates if the user can view team tab
     * @apiSuccess {Boolean}  user.can_create_task  Indicates if the user can create tasks
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     * {
     *   "access_token": "51|d6HvWGk6zY1aqqRms5pkp6Pb6leBs7zaW4IAWGvQ5d00b8be",
     *   "token_type": "bearer",
     *   "expires_in": "2024-07-12T11:59:31+00:00",
     *   "user": {
     *       "id": 1,
     *       "full_name": "Admin",
     *       "email": "johndoe@example.com",
     *       "url": "",
     *       "company_id": 1,
     *       "avatar": "",
     *       "screenshots_active": 1,
     *       "manual_time": 0,
     *       "computer_time_popup": 300,
     *       "blur_screenshots": false,
     *       "web_and_app_monitoring": true,
     *       "screenshots_interval": 5,
     *       "active": 1,
     *       "deleted_at": null,
     *       "created_at": "2023-10-26T10:26:17.000000Z",
     *       "updated_at": "2024-02-15T19:06:42.000000Z",
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
     *       "last_activity": "2023-10-26 10:26:17",
     *       "online": false,
     *       "can_view_team_tab": true,
     *       "can_create_task": true
     *   }
     * }
     */
    /**
     * @apiDefine ScreenshotObject
     * @apiSuccess {Integer}  id               ID of the screenshot
     * @apiSuccess {Integer}  time_interval_id Time interval ID to which the screenshot belongs
     * @apiSuccess {String}   path             Path to the screenshot
     * @apiSuccess {String}   created_at       Timestamp when the screenshot was created
     * @apiSuccess {String}   updated_at       Timestamp when the screenshot was last updated
     * @apiSuccess {String}   [deleted_at]     Timestamp when the screenshot was deleted (if applicable)
     * @apiSuccess {String}   [thumbnail_path] Path to the thumbnail of the screenshot (if applicable)
     * @apiSuccess {Boolean}  important        Indicates if the screenshot is marked as important
     * @apiSuccess {Boolean}  is_removed       Indicates if the screenshot is removed
     *  @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "data": {
     *      "id": 1,
     *      "time_interval_id": 1,
     *      "path": "uploads\/screenshots\/1_1_1.png",
     *      "created_at": "2020-01-23T09:42:26+00:00",
     *      "updated_at": "2020-01-23T09:42:26+00:00",
     *      "deleted_at": null,
     *      "thumbnail_path": null,
     *      "important": false,
     *      "is_removed": false
     *    }
     *  }
     */

    /**
     * @apiDefine ScreenshotParams
     */

    /**
     * @apiDefine TimeIntervalObject
     * @apiSuccess {Integer}  id             ID of the time interval
     * @apiSuccess {Integer}  task_id        ID of the task
     * @apiSuccess {String}   start_at       Start timestamp of the time interval
     * @apiSuccess {String}   end_at         End timestamp of the time interval
     * @apiSuccess {String}   created_at     Creation timestamp
     * @apiSuccess {String}   updated_at     Last update timestamp
     * @apiSuccess {String}   [deleted_at]   Deletion timestamp (if applicable)
     * @apiSuccess {String}   user_id        The ID of the user
     * @apiSuccess {Boolean}  is_manual      Indicates whether the time was logged manually (true) or automatically
     * @apiSuccess {Integer}  activity_fill  Activity fill percentage
     * @apiSuccess {Integer}  mouse_fill     Mouse activity fill percentage
     * @apiSuccess {Integer}  keyboard_fill  Keyboard activity fill percentage
     * @apiSuccess {Integer}  location       Additional location information, if available
     * @apiSuccess {Integer}  screenshot_id  The ID of the screenshot associated with this interval
     * @apiSuccess {Integer}  has_screenshot Indicates if there is a screenshot for this interval
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    {
     *       "id": 1,
     *       "task_id": 1,
     *       "start_at": "2023-10-26 10:21:17",
     *       "end_at": "2023-10-26 10:26:17",
     *       "created_at": "2023-10-26T10:26:17.000000Z",
     *       "updated_at": "2023-10-26T10:26:17.000000Z",
     *       "deleted_at": null,
     *       "user_id": 2,
     *       "is_manual": false,
     *       "activity_fill": 60,
     *       "mouse_fill": 47,
     *       "keyboard_fill": 13,
     *       "location": null,
     *       "screenshot_id": null,
     *       "has_screenshot": true
     *   },...
     *  }
     */

    /**
     * @apiDefine TimeIntervalParams
     */

    /**
     * @apiDefine InvitationObject
     */

    /**
     * @apiDefine PriorityObject
     * @apiSuccess {Boolean}  status        Indicates if the request was successful
     * @apiSuccess {Boolean}  success       Indicates if the request was successful
     * @apiSuccess {Object}   data          Response object
     * @apiSuccess {Integer}  data.id       Priority ID
     * @apiSuccess {String}   data.name     Priority name
     * @apiSuccess {String}   data.color    Priority color (can be null)
     * @apiSuccess {String}   data.created_at Creation timestamp
     * @apiSuccess {String}   data.updated_at Update timestamp
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "status": 200,
     *    "success": true,
     *    "data": {
     *      "id": 1,
     *      "name": "Normal",
     *      "created_at": "2023-10-26T10:26:17.000000Z",
     *      "updated_at": "2024-07-12T17:57:40.000000Z",
     *      "color": null
     *    }
     *  }
     */

    /**
     * @apiDefine ProjectParams
     * @apiParam {Object}   [filters]                 Filters to apply to the project list.
     * @apiParam {Object}   [filters.id]              Filter by project ID.
     * @apiParam {String}   [filters.name]            Filter by project name.
     * @apiParam {String}   [filters.description]     Filter by project description.
     * @apiParam {String}   [filters.created_at]      Filter by project creation date.
     * @apiParam {String}   [filters.updated_at]      Filter by project update date.
     * @apiParam {Integer}  [page=1]                  Page number for pagination.
     * @apiParam {Integer}  [perPage=15]              Number of items per page.
     */
    /**
     * @apiDefine StatusObject
     * @apiSuccess {Number}   id             The ID of the status.
     * @apiSuccess {String}   name           The name of the status.
     * @apiSuccess {Boolean}  active         Indicates if the status is active.
     * @apiSuccess {String}   color          The color of the status (in HEX).
     * @apiSuccess {Number}   order          The sort order of the status.
     * @apiSuccess {String}   created_at     The creation timestamp.
     * @apiSuccess {String}   updated_at     The last update timestamp.
     *
     * @apiSuccessExample {json} Success Response Example:
     *  HTTP/1.1 200 OK
     *  {
     *      "id": 1,
     *      "name": "Normal",
     *      "active": true,
     *      "color": "#363334",
     *      "order": 1,
     *      "created_at": "2024-08-26T10:47:30.000000Z",
     *      "updated_at": "2024-08-26T10:48:35.000000Z"
     *  }
     */
    /**
     * @apiDefine ParamTimeInterval
     *
     * @apiParam {String}   start_at  The start datetime for the interval
     * @apiParam {String}   end_at    The end datetime for the interval
     * @apiParam {Integer}  user_id   The ID of the user
     * @apiParamExample {json} Request Example
     * {
     *   "start_at":  "2024-08-16T12:32:11.000000Z",
     *   "end_at":  "2024-08-17T12:32:11.000000Z",
     *   "user_id": 1
     * }
     */
    /**
     * @apiDefine AuthHeader
     * @apiHeader {String} Authorization Token for user auth
     * @apiHeaderExample {json} Authorization Header Example
     *  {
     *    "Authorization": "bearer 16184cf3b2510464a53c0e573c75740540fe..."
     *  }
     */
    /**
     * @apiDefine 400Error
     * @apiError (Error 4xx) {String}   message     Message from server
     * @apiError (Error 4xx) {Boolean}  success     Indicates erroneous response when `FALSE`
     * @apiError (Error 4xx) {String}   error_type  Error type
     *
     * @apiVersion 1.0.0
     */

    /**
     * @apiDefine UnauthorizedError
     * @apiErrorExample {json} Unauthorized
     *  HTTP/1.1 401 Unauthorized
     *  {
     *    "message": "Not authorized",
     *    "error_type": "authorization.unauthorized"
     *  }
     *
     * @apiVersion 1.0.0
     */
    /**
     * @apiDefine ForbiddenError
     * @apiErrorExample {json} Forbidden
     *  HTTP/1.1 403 Forbidden
     *  {
     *    "message": "Access denied to this item",
     *    "error_type": "authorization.forbidden"
     *  }
     *
     * @apiVersion 1.0.0
     */
    /**
     * @apiDefine TotalSuccess
     * @apiSuccess {Boolean} success Indicates if the request was successful
     * @apiSuccess {Object}  data    The response data
     * @apiSuccess {Integer} data.total The total count of items
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "status": 200,
     *    "success": true,
     *    "data": {
     *      "total": 0
     *    }
     *  }
     */
    /**
     * @throws Exception
     */
    public function _index(CattrFormRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        $itemsQuery = $this->getQuery($requestData);

        CatEvent::dispatch(Filter::getBeforeActionEventName(), $requestData);

        $items = $request->header('X-Paginate', true) !== 'false' ? $itemsQuery->paginate() : $itemsQuery->get();

        Filter::process(
            Filter::getActionFilterName(),
            $items,
        );

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$items, $requestData]);

        return responder()->success($items)->respond();
    }

    /**
     * @throws Exception
     */
    protected function getQuery(array $filter = []): Builder
    {
        $model = static::MODEL;
        $model = new $model;

        $query = new Builder($model::getQuery());
        $query->setModel($model);

        $modelScopes = $model->getGlobalScopes();

        foreach ($modelScopes as $key => $value) {
            $query->withGlobalScope($key, $value);
        }

        foreach (Filter::process(Filter::getQueryAdditionalRelationsFilterName(), []) as $with) {
            $query->with($with);
        }

        foreach (Filter::process(Filter::getQueryAdditionalRelationsSumFilterName(), []) as $withSum) {
            $query->withSum(...$withSum);
        }

        QueryHelper::apply($query, $model, $filter);

        return Filter::process(
            Filter::getQueryFilterName(),
            $query
        );
    }

    /**
     * @throws Throwable
     */
    public function _create(CattrFormRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        CatEvent::dispatch(Filter::getBeforeActionEventName(), [$requestData]);

        /** @var Model $cls */
        $cls = static::MODEL;

        $item = Filter::process(
            Filter::getActionFilterName(),
            $cls::create($requestData),
        );

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$item, $requestData]);

        return responder()->success($item)->respond();
    }

    /**
     * @throws Throwable
     */
    public function _edit(CattrFormRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        throw_unless(is_int($request->get('id')), ValidationException::withMessages(['Invalid id']));

        $itemsQuery = $this->getQuery();

        /** @var Model $item */
        $item = $itemsQuery->get()->collect()->firstWhere('id', $request->get('id'));

        if (!$item) {
            /** @var Model $cls */
            $cls = static::MODEL;
            throw_if($cls::find($request->get('id'))?->count(), new AccessDeniedHttpException);

            throw new NotFoundHttpException;
        }

        CatEvent::dispatch(Filter::getBeforeActionEventName(), [$item, $requestData]);

        $item = Filter::process(Filter::getActionFilterName(), $item->fill($requestData));
        $item->save();

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$item, $requestData]);

        return responder()->success($item)->respond();
    }

    /**
     * @throws Throwable
     */
    public function _destroy(CattrFormRequest $request): JsonResponse
    {
        $requestId = Filter::process(Filter::getRequestFilterName(), $request->validated('id'));

        throw_unless(is_int($requestId), ValidationException::withMessages(['Invalid id']));

        $itemsQuery = $this->getQuery(['where' => ['id' => $requestId]]);

        /** @var Model $item */
        $item = $itemsQuery->first();
        if (!$item) {
            /** @var Model $cls */
            $cls = static::MODEL;
            throw_if($cls::find($requestId)?->count(), new AccessDeniedHttpException);

            throw new NotFoundHttpException;
        }

        CatEvent::dispatch(Filter::getBeforeActionEventName(), $requestId);

        CatEvent::dispatch(
            Filter::getAfterActionEventName(),
            tap(
                Filter::process(Filter::getActionFilterName(), $item),
                static fn ($item) => $item->delete(),
            )
        );

        return responder()->success()->respond(204);
    }

    /**
     * @throws Exception
     */
    protected function _count(CattrFormRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        CatEvent::dispatch(Filter::getBeforeActionEventName(), $requestData);

        $itemsQuery = $this->getQuery($requestData);

        $count = Filter::process(Filter::getActionFilterName(), $itemsQuery->count());

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$count, $requestData]);

        return responder()->success(['total' => $count])->respond();
    }

    /**
     * @throws Throwable
     */
    protected function _show(CattrFormRequest $request): JsonResponse
    {
        $requestData = Filter::process(Filter::getRequestFilterName(), $request->validated());

        $itemId = (int)$requestData['id'];

        throw_unless($itemId, ValidationException::withMessages(['Invalid id']));

        $filters = [
            'where' => ['id' => $itemId]
        ];

        if (!empty($requestData['with'])) {
            $filters['with'] = $requestData['with'];
        }

        if (!empty($requestData['withSum'])) {
            $filters['withSum'] = $requestData['withSum'];
        }

        CatEvent::dispatch(Filter::getBeforeActionEventName(), $filters);

        $itemsQuery = $this->getQuery($filters ?: []);

        $item = Filter::process(Filter::getActionFilterName(), $itemsQuery->first());

        throw_unless($item, new NotFoundHttpException);

        CatEvent::dispatch(Filter::getAfterActionEventName(), [$item, $filters]);

        return responder()->success($item)->respond();
    }
}
