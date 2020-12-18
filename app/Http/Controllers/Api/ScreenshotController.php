<?php

namespace App\Http\Controllers\Api;

use Filter;
use App\Models\Role;
use App\Models\Screenshot;
use App\Models\TimeInterval;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Auth;
use Route;
use Storage;
use Validator;
use Image;

/**
 * Class ScreenshotController
 */
class ScreenshotController extends ItemController
{
    public function getEventUniqueNamePart(): string
    {
        return 'screenshot';
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function index(Request $request): JsonResponse
    {
        if ($request->get('user_id')) {
            $request->offsetSet('timeInterval.user_id', $request->get('user_id'));
            $request->offsetUnset('user_id');
        }

        if ($request->get('project_id')) {
            $request->offsetSet('timeInterval.task.project_id', $request->get('project_id'));
            $request->offsetUnset('project_id');
        }

        return $this->_index($request);
    }

    public function create(Request $request): JsonResponse
    {
        // Request must contain screenshot
        if (!isset($request->screenshot)) {
            return new JsonResponse(
                Filter::process($this->getEventUniqueName('answer.error.item.create'), [
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => 'screenshot is required',
                ]),
                400
            );
        }

        if (!Storage::exists('uploads/screenshots/thumbs')) {
            Storage::makeDirectory('uploads/screenshots/thumbs');
        }

        $screenStorePath = $request->screenshot->store('uploads/screenshots');
        $absoluteStorePath = Storage::disk()->path($screenStorePath);
        $path = Filter::process($this->getEventUniqueName('request.item.create'), $absoluteStorePath);

        $screenshot = Image::make($path);

        $thumbnail = $screenshot->resize(280, null, static function ($constraint) {
            $constraint->aspectRatio();
        });

        $thumbnailPath = str_replace('uploads/screenshots', 'uploads/screenshots/thumbs', $path);
        Storage::put($thumbnailPath, (string)$thumbnail->encode());

        // Get interval id
        $timeIntervalID = ((int)$request->get('time_interval_id')) ?: null;

        // Pack everything we need
        $screenshotPack = [
            'time_interval_id' => $timeIntervalID,
            'path' => $path,
            'thumbnail_path' => str_replace('.jpg', '-thumb.jpg', $thumbnailPath),
        ];

        $validator = Validator::make(
            $screenshotPack,
            Filter::process(
                $this->getEventUniqueName('validation.item.create'),
                $this->getValidationRules()
            )
        );

        if ($validator->fails()) {
            return new JsonResponse(
                Filter::process($this->getEventUniqueName('answer.error.item.create'), [
                    'error_type' => 'validation',
                    'message' => 'Validation error',
                    'info' => $validator->errors()
                ]),
                400
            );
        }

        $createdScreenshot = Filter::process(
            $this->getEventUniqueName('item.create'),
            Screenshot::create($screenshotPack)
        );

        // Respond to client
        return new JsonResponse(
            Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                'screenshot' => $createdScreenshot,
            ]),
            200
        );
    }

    public function getValidationRules(): array
    {
        return [
            'time_interval_id' => 'exists:time_intervals,id|required',
            'path' => 'required',
        ];
    }

    /**
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

    public function getItemClass(): string
    {
        return Screenshot::class;
    }

    /**
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
    public function count(Request $request): JsonResponse
    {
        return $this->_count($request);
    }

    /**
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
    public function show(Request $request): JsonResponse
    {
        return $this->_show($request);
    }
}
