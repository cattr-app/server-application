<?php

namespace App\Http\Controllers\Api\Reports;

use App\Enums\UniversalReport;
use App\Helpers\ReportHelper;
use App\Http\Requests\Reports\UniversalReport\UniversalReportEditRequest;
use App\Http\Requests\Reports\UniversalReport\UniversalReportRequest;
use App\Http\Requests\Reports\UniversalReport\UniversalReportShowRequest;
use App\Http\Requests\Reports\UniversalReport\UniversalReportStoreRequest;
use App\Http\Requests\Reports\UniversalReport\UniversalReportDestroyRequest;

use App\Jobs\GenerateAndSendReport;
use App\Models\Project;
use App\Models\UniversalReport as ModelsUniversalReport;
use App\Reports\PlannedTimeReportExport;
use App\Reports\UniversalReportExport;
use Carbon\Carbon;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Settings;
use Throwable;

class UniversalReportController
{
    public function index()
    {
        $items = [
            'personal' => [],
            'company' => [],
        ];
        $user = request()->user();

        if (request()->user()->isAdmin()) {
            ModelsUniversalReport::select('id', 'name', 'type')
                ->where([
                    ['type', '=', 'company', 'or'],
                    ['user_id', '=', request()->user()->id, 'or']
                ])
                ->get()
                ->each(function($item) use (&$items) {
                    $item->type === 'company' ? array_push($items['company'], $item->toArray()) : array_push($items['personal'], $item->toArray());
                });

            return responder()->success($items)->respond();
        }

        ModelsUniversalReport::select('id', 'name', 'data_objects', 'main', 'type')->get()->each(function($item) use (&$items) {
            if ($item->main->checkAccess($item->data_objects)) {
                unset($item->data_objects, $item->main);
                $item->type === 'company' ? array_push($items['company'], $item->toArray()) : array_push($items['personal'], $item->toArray());
            }
        });

        return responder()->success($items)->respond();

    }

    public function getMains()
    {
        return responder()->success(UniversalReport::mains())->respond();
    }

    public function getDataObjectsAndFields(Request $request)
    {
        $main = UniversalReport::tryFrom($request->input('main', null));
        // dd($main->dataObjects());
        return responder()->success([
            'fields' => $main->fields(),
            'dataObjects' => $main->dataObjects(),
            'charts' => $main->charts(),
            ])->respond();
    }

    public function store(UniversalReportStoreRequest $request)
    {
        $user = $request->user();

        if ($request->type === 'company') {
            if ($request->user()->isAdmin()) {
                $report = $user->universalReports()->create([
                    'name' => $request->name,
                    'type' => $request->type,
                    'main' => $request->main,
                    'data_objects' => $request->dataObjects,
                    'fields' => $request->fields,
                    'charts' => $request->charts,
                ]);
                return responder()->success(['message' => "Отчёт успешно сохранён", 'id' => $report->id])->respond(200);
            } else {
                return responder()->error(500, 'Права пользователя не позволяют сохранить отчёт для компании')->respond(500);
            }
        }

        $report = $user->universalReports()->create([
            'name' => $request->name,
            'type' => $request->type,
            'main' => $request->main,
            'data_objects' => $request->dataObjects,
            'fields' => $request->fields,
            'charts' => $request->charts,
        ]);

        return responder()->success(['message' => "Отчёт успешно сохранён", 'id' => $report->id])->respond(200);
    }

    public function show(UniversalReportShowRequest $request)
    {
        return responder()->success(ModelsUniversalReport::find($request->id))->respond();
    }

    public function edit(UniversalReportEditRequest $request)
    {
        if($request->type === 'company') {
            if($request->user()->isAdmin()) {
                ModelsUniversalReport::where('id', $request->id)->update([
                    'name' => $request->name,
                    'type' => $request->type,
                    'main' => $request->main,
                    'data_objects' => $request->dataObjects,
                    'fields' => $request->fields,
                    'charts' => $request->charts,
                ]);
            }
        }

        ModelsUniversalReport::where('id', $request->id)->update([
            'name' => $request->name,
            'type' => $request->type,
            'main' => $request->main,
            'data_objects' => $request->dataObjects,
            'fields' => $request->fields,
            'charts' => $request->charts,
        ]);
    }


    public function __invoke(UniversalReportRequest $request): JsonResponse
    {
        // dd(Carbon::parse());
        return responder()->success(
            UniversalReportExport::init(
                $request->id,
                Carbon::parse($request->start_at) ?? Carbon::parse(),
                Carbon::parse($request->end_at) ?? Carbon::parse(),
                Settings::scope('core')->get('timezone', 'UTC'),
                $request->user()?->timezone ?? 'UTC',

            )->collection()->all(),
        )->respond();
    }

    public function destroy(UniversalReportDestroyRequest $request)
    {
        ModelsUniversalReport::find($request->input('id', null))->delete();

        return responder()->success()->respond(204);
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
