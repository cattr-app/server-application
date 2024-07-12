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
     * @apiDefine UserObject
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
     *    "res": {
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
     * @apiSuccess {Integer}  activity_fill  Activity fill percentage
     * @apiSuccess {Integer}  mouse_fill     Mouse activity fill percentage
     * @apiSuccess {Integer}  keyboard_fill  Keyboard activity fill percentage
     * @apiSuccess {Integer}  user_id        ID of the user
     * @apiSuccessExample {json} Response Example
     * {
     *   "id": 1,
     *   "task_id": 1,
     *   "start_at": "2006-05-31 16:15:09",
     *   "end_at": "2006-05-31 16:20:07",
     *   "created_at": "2018-09-25 06:15:08",
     *   "updated_at": "2018-09-25 06:15:08",
     *   "deleted_at": null,
     *   "activity_fill": 42,
     *   "mouse_fill": 43,
     *   "keyboard_fill": 43,
     *   "user_id": 1
     * }
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
     */

    /**
     * @apiDefine ProjectObject
     */
    /**
     * @apiDefine StatusObject
     */
    /**
     * @apiDefine TaskParams
     */
    /**
     * @apiDefine TaskObject
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
