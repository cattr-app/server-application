<?php

namespace App\Http\Controllers\Api;

use App\Contracts\ScreenshotService;
use App\Http\Requests\ScreenshotRequest;
use Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ScreenshotController
{
    protected ScreenshotService $screenshotService;

    public function __construct(ScreenshotService $screenshotService)
    {
        $this->screenshotService = $screenshotService;
    }

    /**
     * @api             {get} /screenshot/:id Screenshot
     * @apiDescription  Get Screenshot
     *
     * @apiVersion      3.5.0
     * @apiName         Show
     * @apiGroup        Screenshot
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   time_intervals_show
     * @apiPermission   time_intervals_full_access
     *
     * @apiParam {Integer}  id  ID of the target Time interval
     *
     * @apiSuccess {Raw}    <>  Screenshot data
     *
     * @apiUse          400Error
     * @apiUse          NotFoundError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */
    /**
     * @param ScreenshotRequest $request
     * @return BinaryFileResponse
     */
    public function show(ScreenshotRequest $request): BinaryFileResponse
    {
        $path = $this->screenshotService->getScreenshotPath($request->route('id'));
        if (!Storage::exists($path)) {
            abort(404);
        }

        $fullPath = Storage::path($path);
        return response()->file($fullPath);
    }

    /**
     * @api             {get} /screenshot/:id Thumb
     * @apiDescription  Get Screenshot Thumbnail
     *
     * @apiVersion      3.5.0
     * @apiName         ShowThumb
     * @apiGroup        Screenshot
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   time_intervals_show
     * @apiPermission   time_intervals_full_access
     *
     * @apiParam {Integer}  id  ID of the target Time interval
     *
     * @apiSuccess {Raw}    <>  Screenshot data
     *
     * @apiUse          400Error
     * @apiUse          NotFoundError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */
    /**
     * @param ScreenshotRequest $request
     * @return BinaryFileResponse
     */
    public function showThumb(ScreenshotRequest $request): BinaryFileResponse
    {
        $path = $this->screenshotService->getThumbPath($request->route('id'));
        if (!Storage::exists($path)) {
            abort(404);
        }

        $fullPath = Storage::path($path);
        return response()->file($fullPath);
    }

    /**
     * @apiDeprecated since 3.5.0
     * @api             {get,post} /screenshots/list List
     * @apiDescription  Get list of Screenshots
     *
     * @apiVersion      1.0.0
     * @apiName         List
     * @apiGroup        Screenshot
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   screenshots_list
     * @apiPermission   screenshots_full_access
     *
     * @apiUse          UserParams
     *
     * @apiParamExample {json} Request Example
     *  {
     *      "id":               [">", 1],
     *      "time_interval_id": ["=", [1,2,3]],
     *      "user_id":          ["=", [1,2,3]],
     *      "project_id":       ["=", [1,2,3]],
     *      "path":             ["like", "%lorem%"],
     *      "created_at":       [">", "2019-01-01 00:00:00"],
     *      "updated_at":       ["<", "2019-01-01 00:00:00"]
     *  }
     *
     * @apiUse          UserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  [
     *    {
     *      "id": 1,
     *      "time_interval_id": 1,
     *      "path": "uploads\/screenshots\/1_1_1.png",
     *      "created_at": "2020-01-23T09:42:26+00:00",
     *      "updated_at": "2020-01-23T09:42:26+00:00",
     *      "deleted_at": null,
     *      "thumbnail_path": null,
     *      "important": false,
     *      "is_removed": false
     *    },
     *    {
     *      "id": 2,
     *      "time_interval_id": 2,
     *      "path": "uploads\/screenshots\/1_1_2.png",
     *      "created_at": "2020-01-23T09:42:26+00:00",
     *      "updated_at": "2020-01-23T09:42:26+00:00",
     *      "deleted_at": null,
     *      "thumbnail_path": null,
     *      "important": false,
     *      "is_removed": false
     *    }
     *  ]
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */
    /**
     * @apiDeprecated since 3.5.0
     * Remove the specified resource from storage
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function destroy(Request $request): JsonResponse
    {
        if (!isset($request->id)) {
            return new JsonResponse(
                Filter::process($this->getEventUniqueName('answer.error.item.remove'), [
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => 'screenshot id is required',
                ]),
                400
            );
        }

        // Get screenshot model
        /** @var Screenshot $screenshotModel */
        $screenshotModel = $this->getItemClass();

        // Find exact screenshot to be deleted
        $screenshotToDel = $screenshotModel::where('id', $request->get('id'))->firstOrFail();

        // Get associated time interval
        $thisScreenshotTimeInterval = TimeInterval::where('id', $screenshotToDel->time_interval_id)->firstOrFail();

        if (auth()->user()->cannot('destroy', $thisScreenshotTimeInterval)) {
            return new JsonResponse([
                "message" => "This action is unauthorized",
                "error_type" => "authorization.forbidden"
            ], 403);
        }

        // If this screenshot is last
        if ((int)$thisScreenshotTimeInterval->screenshots_count <= 1) {
            // Delete interval with it
            $thisScreenshotTimeInterval->delete();
        } else {
            // Or screenshot only otherwise
            $screenshotToDel->delete();
        }

        return new JsonResponse(['message' => 'Screenshot successfully deleted']);
    }

    /**
     * @api             {post} /screenshots/create Create
     * @apiDescription  Create Screenshot
     *
     * @apiVersion     1.0.0
     * @apiName        Create
     * @apiGroup       Screenshot
     *
     * @apiParam {Integer}  time_interval_id  Time Interval ID
     * @apiParam {Binary}   screenshot        File
     *
     * @apiParamExample {json} Simple-Request Example
     *  {
     *    "time_interval_id": 1,
     *    "screenshot": <binary data>
     *  }
     *
     * @apiSuccess {Object}   res      User
     *
     * @apiUse ScreenshotObject
     *
     * @apiSuccessExample {json} Response Example
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
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */
    /**
     * @apiDeprecated since 3.5.0
     * @api             {post} /screenshots/remove Destroy
     * @apiDescription  Destroy Screenshot
     *
     * @apiVersion      1.0.0
     * @apiName         Destroy
     * @apiGroup        Screenshot
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   screenshots_remove
     * @apiPermission   screenshots_full_access
     *
     * @apiParam {Integer}  id  ID of the target screenshot
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

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /screenshots/bulk-create Bulk Create
     * @apiDescription  Create Screenshot
     *
     * @apiVersion      1.0.0
     * @apiName         Bulk Create
     * @apiGroup        Screenshot
     *
     * @apiPermission   screenshots_bulk_create
     * @apiPermission   screenshots_full_access
     */

    /**
     * @apiDeprecated  since 3.5.0
     * @api             {get,post} /screenshot/count Count
     * @apiDescription  Count Screenshots
     *
     * @apiVersion      1.0.0
     * @apiName         Count
     * @apiGroup        Screenshot
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccess {String}   total    Amount of projects that we have
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
    /**
     * @apiDeprecated since 3.5.0
     * @api             {post} /screenshots/show Show
     * @apiDescription  Show Screenshot
     *
     * @apiVersion      1.0.0
     * @apiName         Show
     * @apiGroup        Screenshot
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   screenshots_show
     * @apiPermission   screenshots_full_access
     *
     * @apiParam {Integer}  id ID
     *
     * @apiUse          ScreenshotParams
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "id": 1,
     *    "time_interval_id": ["=", [1,2,3]],
     *    "path": ["like", "%lorem%"],
     *    "created_at": [">", "2019-01-01 00:00:00"],
     *    "updated_at": ["<", "2019-01-01 00:00:00"]
     *  }
     *
     * @apiUse          ScreenshotObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *   "id": 1,
     *   "time_interval_id": 1,
     *   "path": "uploads\/screenshots\/1_1_1.png",
     *   "created_at": "2020-01-23T09:42:26+00:00",
     *   "updated_at": "2020-01-23T09:42:26+00:00",
     *   "deleted_at": null,
     *   "thumbnail_path": null,
     *   "important": false,
     *   "is_removed": false
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     */
}
