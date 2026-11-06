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

    public function getBases()
    {
        return responder()->success(UniversalReportBase::bases())->respond();
    }

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

    public function show(UniversalReportShowRequest $request)
    {
        $report = UniversalReport::findOrFail($request->id);
        $this->authorizeView($report, $request);

        return responder()->success($report)->respond();
    }

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

    public function destroy(UniversalReportDestroyRequest $request)
    {
        $report = UniversalReport::findOrFail($request->input('id'));

        if (!$report->canBeManagedBy($request->user())) {
            throw new NotEnoughRightsException('User rights do not allow deleting the report');
        }

        $report->delete();

        return responder()->success()->respond(204);
    }

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
