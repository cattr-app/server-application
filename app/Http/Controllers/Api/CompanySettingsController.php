<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanySettings\UpdateCompanySettingsRequest;
use App\Http\Transformers\CompanySettingsTransformer;
use App\Models\Priority;
use Illuminate\Http\JsonResponse;
use Settings;

class CompanySettingsController extends Controller
{
    /**
     * @api             {get} /company-settings/ List
     * @apiDescription  Returns all company settings.
     *
     * @apiVersion      4.0.0
     * @apiName         ListCompanySettings
     * @apiGroup        Company Settings
     *
     * @apiUse          AuthHeader
     *
     * @apiVersion      4.0.0
     * @apiName         ListCompanySettings
     * @apiGroup        Company Settings
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccess {String}   timezone                        The timezone setting for the company.
     * @apiSuccess {String}   language                        The language setting for the company.
     * @apiSuccess {Integer}  work_time                       The configured work time in hours.
     * @apiSuccess {Array}    color                           Array of colors configured for the company settings.
     * @apiSuccess {Array}    internal_priorities             Array of internal priorities.
     * @apiSuccess {Integer}  internal_priorities.id          The unique ID of the priority.
     * @apiSuccess {String}   internal_priorities.name        The name of the priority.
     * @apiSuccess {String}   internal_priorities.created_at  The creation timestamp of the priority.
     * @apiSuccess {String}   internal_priorities.updated_at  The last update timestamp of the priority.
     * @apiSuccess {String}   internal_priorities.color       The color associated with the priority.
     * @apiSuccess {Integer}  heartbeat_period                The period for heartbeat checks in seconds.
     * @apiSuccess {Boolean}  auto_thinning                   Indicates if automatic thinning of old data is enabled.
     * @apiSuccess {Integer}  screenshots_state               The current state of screenshot monitoring.
     * @apiSuccess {Integer}  env_screenshots_state           The environmental screenshot state.
     * @apiSuccess {Integer}  default_priority_id             The default priority ID.
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    {
     *     "timezone": "UTC",
     *     "language": "ru",
     *     "work_time": 0,
     *     "color": [],
     *     "internal_priorities": [
     *    {
     *      "id": 1,
     *      "name": "Normal",
     *       "created_at": "2023-10-26T10:26:17.000000Z",
     *       "updated_at": "2024-07-12T17:57:40.000000Z",
     *       "color": null
     *    },
     *    {
     *       "id": 2,
     *       "name": "Normal",
     *       "created_at": "2023-10-26T10:26:17.000000Z",
     *       "updated_at": "2024-06-21T10:06:50.000000Z",
     *       "color": "#49E637"
     *    },
     *    {
     *       "id": 3,
     *       "name": "High",
     *       "created_at": "2023-10-26T10:26:17.000000Z",
     *       "updated_at": "2024-06-21T10:07:00.000000Z",
     *       "color": "#D40C0C"
     *    },
     *    {
     *       "id": 5,
     *       "name": "Normal",
     *       "created_at": "2024-07-12T17:10:54.000000Z",
     *       "updated_at": "2024-07-12T17:10:54.000000Z",
     *       "color": null
     *    }
      *],
     *       "heartbeat_period": 60,
     *       "auto_thinning": true,
     *       "screenshots_state": 1,
     *       "env_screenshots_state": -1,
     *       "default_priority_id": 2
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     */
    public function index(): JsonResponse
    {
        return responder()->success(
            array_merge(
                Settings::scope('core')->all(),
                ['internal_priorities' => Priority::all()]
            ),
            new CompanySettingsTransformer
        )->respond();
    }

    /**
     * @param UpdateCompanySettingsRequest $request
     *
     * @return JsonResponse
     *
     * @api             {patch} /company-settings/ Update
     * @apiDescription  Updates the specified company settings.
     *
     * @apiVersion      4.0.0
     * @apiName         UpdateCompanySettings
     * @apiGroup        Company Settings
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {String}   timezone                        The timezone setting for the company.
     * @apiParam {String}   language                        The language setting for the company.
     * @apiParam {Integer}  work_time                       The configured work time in hours.
     * @apiParam {Array}    color                           Array of colors configured for the company settings.
     * @apiParam {Array}    internal_priorities             Array of internal priorities.
     * @apiParam {Integer}  internal_priorities.id          The unique ID of the priority.
     * @apiParam {String}   internal_priorities.name        The name of the priority.
     * @apiParam {String}   internal_priorities.created_at  The creation timestamp of the priority.
     * @apiParam {String}   internal_priorities.updated_at  The last update timestamp of the priority.
     * @apiParam {String}   internal_priorities.color       The color associated with the priority.
     * @apiParam {Integer}  heartbeat_period                The period for heartbeat checks in seconds.
     * @apiParam {Boolean}  auto_thinning                   Indicates if automatic thinning of old data is enabled.
     * @apiParam {Integer}  screenshots_state               The current state of screenshot monitoring.
     * @apiParam {Integer}  env_screenshots_state           The environmental screenshot state.
     * @apiParam {Integer}  default_priority_id             The default priority ID.
     *
     * @apiParamExample {json} Request Example
     * { "timezone" : "Europe/Moscow",
     *  "language" : "en",
     *  "work_time" : 0,
     *  "color" : [],
     *  "internal_priorities" :  [
     *   {
     *      "id" : 1,
     *      "name" : "Normal",
     *      "created_at" : "2023-10-26T10:26:17.000000Z",
     *      "updated_at" : "2024-07-12T17:57:40.000000Z",
     *      "color" : null
     *    },
     *    {
     *      "id" : 2,
     *      "name" : "Normal",
     *      "created_at" : "2023-10-26T10:26:17.000000Z",
     *      "updated_at" : "2024-06-21T10:06:50.000000Z",
     *      "color" : "#49E637"},
     *   {
     *      "id" : 3,
     *      "name" : "High",
     *      "created_at" : "2023-10-26T10:26:17.000000Z",
     *      "updated_at" : "2024-06-21T10:07:00.000000Z",
     *      "color" : "#D40C0C"
     *   },{
     *      "id" : 5,
     *      "name" : "Normal",
     *      "created_at" : "2024-07-12T17:10:54.000000Z",
     *      "updated_at" : "2024-07-12T17:10:54.000000Z",
     *      "color" : null
     *   }
     *  ],
     *  "heartbeat_period" : 60,
     *  "auto_thinning" : true,
     *  "screenshots_state" : 1,
     *  "env_screenshots_state" : -1,
     *  "default_priority_id" : 2
     * }
     *
     * @apiSuccess {Array}   data  Contains an array of settings that changes were applied to
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 204 No Content
     *  {
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     *
     */
    public function update(UpdateCompanySettingsRequest $request): JsonResponse
    {
        Settings::scope('core')->set($request->validated());

        return responder()->success()->respond(204);
    }
    /**
     * @api             {get} /offline-sync/public-key Get Offline Sync Public Key
     * @apiDescription  Retrieves the public key for offline synchronization.
     *
     * @apiVersion      4.0.0
     * @apiName         GetOfflineSyncPublicKey
     * @apiGroup        OfflineSync
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   offline_sync_view
     * @apiPermission   offline_sync_full_access
     *
     * @apiSuccess {Boolean} success Indicates if the operation was successful.
     * @apiSuccess {Object} data The response data.
     * @apiSuccess {String} data.key The public key for offline synchronization.
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *       "key": null
     *  }
     * @apiError (Error 401) Unauthorized The user is not authorized to access this resource.
     * @apiError (Error 403) Forbidden The user does not have the necessary permissions.
     * @apiError (Error 404) NotFound The requested resource was not found.
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     * @apiUse         ForbiddenError
     */
    public function getOfflineSyncPublicKey(): JsonResponse
    {
        return responder()->success(
            [ 'key' => Settings::scope('core.offline-sync')->get('public_key')],
        )->respond();
    }
}
