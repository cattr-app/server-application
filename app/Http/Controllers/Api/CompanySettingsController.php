<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanySettings\IndexCompanySettingsRequest;
use App\Http\Requests\CompanySettings\UpdateCompanySettingsRequest;
use App\Http\Resources\CompanySettings;
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
     * @return JsonResponse
     *
     * @api             {get} /company-settings/index List
     * @apiDescription  Returns all company settings.
     *
     * @apiVersion      1.0.0
     * @apiName         ListCompanySettings
     * @apiGroup        Company Settings
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccess {Array}   data  Contains an array of all company settings
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
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
    public function index(IndexCompanySettingsRequest $request): CompanySettings
    {
        $settings = $this->settings->all();
        $priorities = $this->priorities->all();

        $data = $settings;
        $data['internal_priorities'] = $priorities;

        return new CompanySettings($data);
    }

    /**
     * @param UpdateCompanySettingsRequest $request
     * @return JsonResponse
     *
     * @api             {patch} /company-settings/update Update
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
     * @apiSuccess {Array}   data  Contains an array of settings that changes were applied to
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
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
    public function update(UpdateCompanySettingsRequest $request): CompanySettings
    {
        $settings = $this->settings->set($request->validated());
        $priorities = $this->priorities->all();

        $data = $settings;
        $data['internal_priorities'] = $priorities;

        return new CompanySettings($data);
    }
}
