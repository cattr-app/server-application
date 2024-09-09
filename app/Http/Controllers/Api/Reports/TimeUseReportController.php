<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Requests\Reports\TimeUseReportRequest;
use App\Models\User;
use App\Reports\TimeUseReportExport;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Settings;

/**
 * Class TimeUseReportController
 *
 */
class TimeUseReportController
{
    /**
     * @api             {post} /api/report/time Get User Time Report
     * @apiDescription  Retrieves the time report for specified users within a given time range.
     *
     * @apiVersion      4.0.0
     * @apiName         GetUserTimeReport
     * @apiGroup        Reports
     *
     * @apiParam {String}   start_at       The start date and time for the report in ISO 8601 format.
     * @apiParam {String}   end_at         The end date and time for the report in ISO 8601 format.
     * @apiParam {String}   user_timezone  The timezone of the user. Example: `Asia/Omsk`.
     * @apiParam {Array}    users          List of user IDs to include in the report.
     *
     * @apiParamExample {json} Request Example:
     * {
     *   "start_at": "2023-11-01T16:15:09Z",
     *   "end_at": "2023-11-30T23:59:07Z",
     *   "user_timezone": "Asia/Omsk",
     *   "users": [7]
     * }
     *
     * @apiSuccess {Object[]}   data               List of users and their respective time logs.
     * @apiSuccess {Number}     data.time          Total time logged by the user within the specified period (in seconds).
     * @apiSuccess {Object}     data.user          User information.
     * @apiSuccess {Number}     data.user.id       User ID.
     * @apiSuccess {String}     data.user.email    User's email address.
     * @apiSuccess {String}     data.user.full_name  User's full name.
     * @apiSuccess {Object[]}   data.tasks         List of tasks the user has logged time for.
     * @apiSuccess {Number}     data.tasks.time    Time logged for the task (in seconds).
     * @apiSuccess {Number}     data.tasks.task_id Task ID.
     * @apiSuccess {String}     data.tasks.task_name  Task name.
     * @apiSuccess {Number}     data.tasks.project_id Project ID associated with the task.
     * @apiSuccess {String}     data.tasks.project_name Project name associated with the task.
     *
     * @apiSuccessExample {json} Success Response:
     *  HTTP/1.1 200 OK
     *  {
     *    "status": 200,
     *    "success": true,
     *    "data": [
     *        {
     *            "time": 2151975,
     *            "user": {
     *                "id": 7,
     *                "email": "projectManager1231@example.com",
     *                "full_name": "Dr. Adaline Toy"
     *            },
     *            "tasks": [
     *                {
     *                    "time": 307425,
     *                    "task_id": 56,
     *                    "task_name": "Similique enim aspernatur.",
     *                    "project_id": 159,
     *                    "project_name": "Voluptas ab et ea."
     *                },
     *                ...
     *            ]
     *        }
     *    ]
     *  }

     *
     * @apiUse 400Error
     * @apiUse UnauthorizedError
     * @apiUse ForbiddenError
     */

    public function __invoke(TimeUseReportRequest $request): JsonResponse
    {
        $companyTimezone = Settings::scope('core')->get('timezone', 'UTC');

        return responder()->success(
            TimeUseReportExport::init(
                $request->input('users') ?? User::all()->pluck('id')->toArray(),
                Carbon::parse($request->input('start_at'))->setTimezone($companyTimezone),
                Carbon::parse($request->input('end_at'))->setTimezone($companyTimezone),
                $companyTimezone
            )->collection()->all(),
        )->respond();
    }
}
