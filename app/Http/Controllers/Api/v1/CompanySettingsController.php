<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\v1\CompanySettings\UpdateCompanySettingsRequest;
use App\Models\Priority;
use App\Services\CoreSettingsService;
use Illuminate\Http\JsonResponse;

class CompanySettingsController extends Controller
{
    /**
     * @var CoreSettingsService
     */
    protected CoreSettingsService $settings;

    /**
     * @var Priority
     */
    protected Priority $priorities;

    /**
     * CompanySettingsController constructor.
     * @param CoreSettingsService $settings
     * @param Priority $priorities
     */
    public function __construct(CoreSettingsService $settings, Priority $priorities)
    {
        parent::__construct();

        $this->settings = $settings;
        $this->priorities = $priorities;
    }

    /**
     * Returns the controller rules.
     *
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'index' => 'company-settings.index',
            'update' => 'company-settings.update',
        ];
    }

    /**
     * @return JsonResponse
     *
     * @api             {get} /v1/company-settings/index List
     * @apiDescription  Returns all company settings.
     *
     * @apiVersion      1.0.0
     * @apiName         ListCompanySettings
     * @apiGroup        Company Settings
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Array}   data  Contains an array of all company settings
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "data": {
     *      "timezone": "Europe/Moscow",
     *      "language": "en",
     *      "work_time": 0,
     *      "color": [
     *        {
     *          "start": 0,
     *          "end": 0.75,
     *          "color": "#ffb6c2"
     *        },
     *        {
     *          "start": 0.76,
     *          "end": 1,
     *          "color": "#93ecda"
     *        },
     *        {
     *          "start": 1,
     *          "end": 0,
     *          "color": "#3cd7b6",
     *          "isOverTime": true
     *        }
     *      ]
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     *
     */
    public function index(): JsonResponse
    {
        $settings = $this->settings->all();
        $priorities = $this->priorities->all();

        $data = $settings;
        $data['internal_priorities'] = $priorities;

        return new JsonResponse([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * @param UpdateCompanySettingsRequest $request
     * @return JsonResponse
     *
     * @api             {patch} /v1/company-settings/update Update
     * @apiDescription  Updates the specified company settings.
     *
     * @apiVersion      1.0.0
     * @apiName         UpdateCompanySettings
     * @apiGroup        Company Settings
     *
     * @apiUse          AuthHeader
     *
     * @apiParam {String} timezone  Time zone
     * @apiParam {String} language  Language code
     * @apiParam {Integer} work_time  The duration of the working day
     * @apiParam {Array} Color  The colors of the progress of the working day
     *
     * @apiParamExample {json} Request Example
     *  {
     *    "timezone": "Europe/Moscow",
     *    "language": "en",
     *    "work_time": 0,
     *    "color": [
     *      {
     *        "start": 0,
     *        "end": 0.75,
     *        "color": "#ffb6c2"
     *      },
     *      {
     *        "start": 0.76,
     *        "end": 1,
     *        "color": "#93ecda"
     *      },
     *      {
     *        "start": 1,
     *        "end": 0,
     *        "color": "#3cd7b6",
     *        "isOverTime": true
     *      }
     *    ]
     *  }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Array}   data  Contains an array of settings that changes were applied to
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "data": {
     *      "timezone": "Europe/Moscow",
     *      "language": "en",
     *      "work_time": 0,
     *      "color": [
     *        {
     *          "start": 0,
     *          "end": 0.75,
     *          "color": "#ffb6c2"
     *        },
     *        {
     *          "start": 0.76,
     *          "end": 1,
     *          "color": "#93ecda"
     *        },
     *        {
     *          "start": 1,
     *          "end": 0,
     *          "color": "#3cd7b6",
     *          "isOverTime": true
     *        }
     *      ]
     *    }
     *  }
     *
     * @apiUse          400Error
     * @apiUse          UnauthorizedError
     *
     */
    public function update(UpdateCompanySettingsRequest $request): JsonResponse
    {
        $settings = $this->settings->set($request->validated());

        return new JsonResponse([
            'success' => true,
            'data' => $settings,
        ]);
    }
}
