<?php

namespace App\Http\Controllers\Api\Reports;

use App\Enums\UniversalReportType;
use App\Enums\UniversalReportBase;
use App\Exceptions\Entities\NotEnoughRightsException;
use App\Helpers\ReportHelper;
use App\Http\Requests\Reports\UniversalReport\UniversalReportEditRequest;
use App\Http\Requests\Reports\UniversalReport\UniversalReportRequest;
use App\Http\Requests\Reports\UniversalReport\UniversalReportShowRequest;
use App\Http\Requests\Reports\UniversalReport\UniversalReportStoreRequest;
use App\Http\Requests\Reports\UniversalReport\UniversalReportDestroyRequest;

use App\Jobs\GenerateAndSendReport;
use App\Models\Project;
use App\Models\UniversalReport;
use App\Reports\PlannedTimeReportExport;
use App\Reports\UniversalReportExport;
use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Settings;
use Throwable;

class UniversalReportController
{
    /**
     * List universal reports
     *
     * Returns the saved universal reports (`id`, `name` and `type` only) grouped by type.
     *
     * Administrators get all company reports and their own personal reports. Other users get only the
     * reports whose data objects (projects or tasks) they have access to; reports based on users are not listed
     * for them.
     *
     * @response array{status: int, success: true, data: array{
     *     company: list<array{id: int, name: string, type: string}>,
     *     personal: list<array{id: int, name: string, type: string}>,
     * }}
     */
    public function index()
    {
        $items = [
            UniversalReportType::COMPANY->value => [],
            UniversalReportType::PERSONAL->value => [],
        ];
        $user = request()->user();

        if (request()->user()->isAdmin()) {
            UniversalReport::select('id', 'name', 'type')
                ->where(fn ($query) => $query
                    ->where('type', UniversalReportType::COMPANY->value)
                    ->orWhere('user_id', $user->id))
                ->get()
                ->each(function ($item) use (&$items) {
                    $items[$item->type->value][] = $item->toArray();
                });

            return responder()->success($items)->respond();
        }

        UniversalReport::select('id', 'name', 'data_objects', 'base', 'type')
            ->where(fn ($query) => $query
                ->where('type', UniversalReportType::COMPANY->value)
                ->orWhere('user_id', $user->id))
            ->get()
            ->each(function ($item) use (&$items) {
                if ($item->base->checkAccess($item->data_objects)) {
                    unset($item->data_objects, $item->base);
                    $items[$item->type->value][] = $item->toArray();
                }
            });

        return responder()->success($items)->respond();
    }

    private function authorizeView(UniversalReport $report, Request $request): void
    {
        if (!$report->canBeViewedBy($request->user())) {
            throw new NotEnoughRightsException('User rights do not allow accessing the report');
        }
    }

    /**
     * List report bases
     *
     * Returns the bases a universal report can be built on: `project`, `user` and `task`.
     *
     * @response array{status: int, success: true, data: list<string>}
     */
    public function getBases()
    {
        return responder()->success(UniversalReportBase::bases())->respond();
    }

    /**
     * Get fields and data objects of a base
     *
     * Returns what a universal report with the given `base` can include: the available `fields` (grouped by
     * section), the selectable `dataObjects` (id and name, plus extra user attributes for the `user` base) and the
     * available `charts`.
     *
     * Administrators can select any data object, other users only their own projects and tasks. For the `user`
     * base `dataObjects` is `null` unless the user is an administrator.
     *
     * @response array{status: int, success: true, data: array{
     *     fields: array<string, list<string>>,
     *     dataObjects: list<array<string, mixed>>|null,
     *     charts: list<string>,
     * }}
     */
    public function getDataObjectsAndFields(Request $request)
    {
        $validated = $request->validate(['base' => ['required', new Enum(UniversalReportBase::class)]]);
        $base = UniversalReportBase::from($validated['base']);

        return responder()->success([
            'fields' => $base->fields(),
            'dataObjects' => $base->dataObjects(),
            'charts' => $base->charts(),
        ])->respond();
    }

    /**
     * Save a universal report
     *
     * Saves the report settings (base, selected data objects, fields and charts) as a company report (`type` is
     * `company`, administrators only) or as a personal report of the current user.
     *
     * Any authenticated user may call it. Saving a company report without administrator rights is rejected with 403.
     *
     * @response array{status: int, success: true, data: array{message: string, id: int}}
     */
    public function store(UniversalReportStoreRequest $request)
    {
        $user = $request->user();

        if ($request->input('type') === UniversalReportType::COMPANY->value) {
            if ($request->user()->isAdmin()) {
                $report = $user->universalReports()->create([
                    'name' => $request->name,
                    'type' => $request->type,
                    'base' => $request->base,
                    'data_objects' => $request->dataObjects,
                    'fields' => $request->fields,
                    'charts' => $request->charts,
                ]);
                return responder()->success(['message' => "The report was saved successfully", 'id' => $report->id])->respond(200);
            } else {
                return throw new NotEnoughRightsException('User rights do not allow saving the report for the company');
            }
        }

        $report = $user->universalReports()->create([
            'name' => $request->name,
            'type' => $request->type,
            'base' => $request->base,
            'data_objects' => $request->dataObjects,
            'fields' => $request->fields,
            'charts' => $request->charts,
        ]);

        return responder()->success(['message' => "The report was saved successfully", 'id' => $report->id])->respond(200);
    }

    /**
     * Get a universal report
     *
     * Returns the saved settings of the report with the given `id`.
     *
     * Any authenticated user may call it.
     *
     * @response array{status: int, success: true, data: array{
     *     id: int,
     *     user_id: int,
     *     name: string,
     *     type: string,
     *     base: string,
     *     data_objects: list<int>,
     *     fields: array<string, list<string>>,
     *     charts: list<string>,
     *     created_at: string|null,
     *     updated_at: string|null,
     * }}
     */
    public function show(UniversalReportShowRequest $request)
    {
        $report = UniversalReport::findOrFail($request->id);
        $this->authorizeView($report, $request);

        return responder()->success($report)->respond();
    }

    /**
     * Update a universal report
     *
     * Replaces the settings of the report with the given `id`. The body of the response is empty.
     *
     * Any authenticated user may call it. Saving a company report (`type` is `company`) without administrator
     * rights is rejected with 403.
     */
    public function edit(UniversalReportEditRequest $request)
    {
        $report = UniversalReport::findOrFail($request->id);
        $user = $request->user();

        $promotesToCompany = $request->input('type') === UniversalReportType::COMPANY->value && !$user->isAdmin();

        if (!$report->canBeManagedBy($user) || $promotesToCompany) {
            throw new NotEnoughRightsException('User rights do not allow saving the report');
        }

        $report->update([
            'name' => $request->name,
            'type' => $request->type,
            'base' => $request->base,
            'data_objects' => $request->dataObjects,
            'fields' => $request->fields,
            'charts' => $request->charts,
        ]);
    }


    /**
     * Generate a universal report
     *
     * Builds the report with the given `id` for the period from `start_at` to `end_at`, both interpreted in the
     * company timezone.
     *
     * Any authenticated user may call it. `reportData` is keyed by the ID of the data object (project, user or
     * task, depending on the base of the report), the shape of its values depends on the base and the selected fields.
     * `reportCharts` is keyed by the selected chart (an empty list if none is selected), each chart has `datasets`
     * with the `label`, colors and `data` by date of the project, user or task.
     *
     * @response array{status: int, success: true, data: array{
     *     reportData: array<string, array<string, mixed>>,
     *     reportName: string,
     *     reportCharts: array<string, array{datasets: array<string, mixed>}>|list<mixed>,
     *     periodDates: list<string>,
     * }}
     */
    public function __invoke(UniversalReportRequest $request): JsonResponse
    {
        $this->authorizeView(UniversalReport::findOrFail($request->input('id')), $request);

        $companyTimezone = Settings::scope('core')->get('timezone', 'UTC');

        return responder()->success(
            UniversalReportExport::init(
                $request->input('id'),
                Carbon::parse($request->input('start_at'))->setTimezone($companyTimezone),
                Carbon::parse($request->input('end_at'))->setTimezone($companyTimezone),
                Settings::scope('core')->get('timezone', 'UTC'),
            )->collection()->all(),
        )->respond();
    }

    /**
     * Delete a universal report
     *
     * Deletes the report with the given `id`. The response has no body.
     *
     * Any authenticated user may call it.
     */
    public function destroy(UniversalReportDestroyRequest $request)
    {
        $report = UniversalReport::findOrFail($request->input('id'));

        if (!$report->canBeManagedBy($request->user())) {
            throw new NotEnoughRightsException('User rights do not allow deleting the report');
        }

        $report->delete();

        return responder()->success()->respond(204);
    }

    /**
     * Download a universal report
     *
     * Generates the report with the given `id` as a file and returns its URL.
     *
     * Any authenticated user may call it.
     */
    public function download(UniversalReportRequest $request): JsonResponse
    {
        $this->authorizeView(UniversalReport::findOrFail($request->input('id')), $request);

        $companyTimezone = Settings::scope('core')->get('timezone', 'UTC');
        $job = new GenerateAndSendReport(
            UniversalReportExport::init(
                $request->id,
                Carbon::parse($request->start_at) ?? Carbon::parse(),
                Carbon::parse($request->end_at) ?? Carbon::parse(),
                Settings::scope('core')->get('timezone', 'UTC'),
                $request->user()?->timezone ?? 'UTC',
            ),
            $request->user(),
            ReportHelper::getReportFormat($request),
        );

        $job->handle();
     //   app(Dispatcher::class)->dispatchSync($job);

        return responder()->success(['url' => $job->getPublicPath()])->respond();
    }
    // /**
    //  * @throws Throwable
    //  */
    // public function download(UniversalReportRequest $request): JsonResponse
    // {
    //     $job = new GenerateAndSendReport(
    //         PlannedTimeReportExport::init(
    //             $request->input('projects', Project::all()->pluck('id')->toArray()),
    //         ),
    //         $request->user(),
    //         ReportHelper::getReportFormat($request),
    //     );

    //     app(Dispatcher::class)->dispatchSync($job);

    //     return responder()->success(['url' => $job->getPublicPath()])->respond();
    // }
}
