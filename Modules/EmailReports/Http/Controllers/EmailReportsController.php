<?php

namespace Modules\EmailReports\Http\Controllers;


use App\Http\Controllers\Api\v1\ItemController;
use App\User;
use Illuminate\Database\Eloquent\Model;
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
                    ->send(new EmailReportMail(date('l\, d Y', strtotime($dates['startAt'])),
                        date('l\, d Y', strtotime($dates['endAt'] ?? 'yesterday')),
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
}
