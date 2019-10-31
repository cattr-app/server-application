<?php

namespace Modules\EmailReports\Http\Controllers;


use App\Http\Controllers\Api\v1\ItemController;
use App\Models\Project;
use App\User;
use Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Mail;
use Modules\EmailReports\Entities\SavedReportsRepository;
use Modules\EmailReports\Mail\EmailReportMail;
use Modules\EmailReports\Models\EmailReports;

class EmailReportsController extends ItemController
{
    /**
     * @var SavedReportsRepository
     */
    private $savedReportsRepository;

    public function __construct(SavedReportsRepository $savedReportsRepository)
    {
        $this->savedReportsRepository = $savedReportsRepository;
    }

    public function send()
    {
        $map = [EmailReports::DAILY, EmailReports::WEEKLY, EmailReports::MONTHLY ];
        $userIds = User::all('id')->pluck('id')->toArray();

        foreach ($map as $frequency) {
            $emailReports = EmailReports::whereFrequency($frequency)->get();

            foreach ($emailReports as $emailReport) {
                if (!EmailReports::checkIsReportSendDay($emailReport->value, $frequency)) {
                    continue;
                }

                $dates = EmailReports::getDatesToWorkWith($frequency);

                $preparedReports = [
                        'emails' => $emailReport->email,
                        'reports' => $this->savedReportsRepository->createReportFromDecodedData($emailReport, $userIds, $dates)
                    ];

                Mail::to(explode(',', $preparedReports['emails']))
                    ->send(new EmailReportMail(date('l\, m Y', strtotime($dates['startAt'])),
                        date('l\, m Y', strtotime($dates['endAt'] ?? 'yesterday')),
                        $preparedReports['reports']));
            }
        }
    }

    /**
     * Returns current item's class name
     *
     * @return string|Model
     */
    public function getItemClass(): string
    {
        return EmailReports::class;
    }

    /**
     * Returns validation rules for current item
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'name'    => 'required',
            'email'    => 'required',
            'frequency'    => 'required',
            'value'    => 'required',
        ];
    }

    /**
     * Returns unique part of event name for current item
     *
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'email-reports';
    }

    public function index(Request $request): JsonResponse
    {
        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.list.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(), $request->all() ?: []
            )
        );

        $items = [];
        foreach ($itemsQuery->get() as $item) {
            $item->project_ids = Project::whereIn('id', json_decode($item->project_ids))->get();
            $items []= $item;
        }

            return response()->json(
            Filter::process(
                $this->getEventUniqueName('answer.success.item.list.result'),
                $items
            )
        );
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ModelNotFoundException
     */
    public function show(Request $request): JsonResponse
    {
        $itemId = is_int($request->get('id')) ? $request->get('id') : false;

        if (!$itemId) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.show'), [
                    'error' => 'Validation fail',
                    'reason' => 'Id invalid',
                ]),
                400
            );
        }

        $filters = [
            'id' => $itemId
        ];
        $request->get('with') ? $filters['with'] = $request->get('with') : false;
        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(), $filters ?: []
            )
        );

        $item = $itemsQuery->first();

        if (!$item) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.show'), [
                    'error' => 'Item not found'
                ]),
                404
            );
        }

        $item->project_ids = Project::whereIn('id', json_decode($item->project_ids))->get();

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.show'), $item)
        );
    }
}
