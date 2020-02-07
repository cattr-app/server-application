<?php

namespace Modules\EmailReports\StatisticExports;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Modules\EmailReports\Mail\ProjectReportEmailReport;
use Modules\EmailReports\Models\EmailReports;
use Modules\Reports\Exports\ProjectExport;

class ProjectReport extends ProjectExport implements FromCollection, ShouldAutoSize
{
    /**
     * @var string
     */
    public $timezone;

    /**
     * @var array
     */
    public $projectIds;

    /**
     * @var string
     */
    public $startAt;

    /**
     * @var string
     */
    public $endAt;

    /**
     * @var array
     */
    public $recipients;

    /**
     * @return Collection
     * @throws \Exception
     */
    public function collection(): Collection
    {
        $queryData = [
            'start_at'=> $this->startAt,
            'end_at' => $this->endAt,
            'uids' => User::all()->pluck('id')->toArray(),
            'pids' => $this->projectIds
        ];

        foreach ($queryData as $key => $value) {
            if (is_null($value)) {
                Log::alert('We are missing ' . $key . ' and EmailReport will not be send for ' . print_r($this->recipients, true));
                return collect([]);
            }
        }

        // Grouping prepared collection to groups by Project Name
        $preparedCollection = $this->getPreparedCollection($queryData)->mapToGroups(function ($item) {
            return [
                $item['name'] => $item['users']
            ];
        });

        $returnableData = collect([]);
        $totalTime = 0;

        // Processing grouped database collection to fill the collection, which will be exported
        foreach ($preparedCollection as $key => $items) {
            $totalTasksTime = 0;
            foreach ($items as $users) {
                foreach ($users as $user) {
                    foreach ($user['tasks'] as $task) {
                        $this->addRowToCollection($returnableData, $key, $user, $task);
                    }
                    $totalTasksTime += $user['tasks_time'];
                }
                $this->addSubtotalToCollection($returnableData, $key, $totalTasksTime);
            }
            $totalTime += $totalTasksTime;
        }

        // Add total row to collection
        $time = (new Carbon('@0'))->diffForHumans(new Carbon("@$totalTime"), true, true, 3);
        $totalTime = (new Carbon('@0'))->floatDiffInHours(new Carbon("@$totalTime"));


        $returnableData->push([
            'Project' => '',
            'User' => '',
            'Task' => 'Total',
            'Time' => "{$time}",
            'Hours (decimal)' => round($totalTime, self::ROUND_DIGITS)
        ]);

        return $returnableData;
    }

    public function sendEmail($emailReport, $frequency)
    {
        $dates = EmailReports::getDatesToWorkWith($frequency);
        $this->startAt = $dates['startAt'];
        $this->endAt = $dates['endAt'];
        $this->projectIds = $emailReport->projects()->pluck('project_id')->toArray();
        $docType = EmailReports::getDocumentType($emailReport);

        $file = $this->collection()->downloadExcel($emailReport->name . '.' . $docType['fsType'], $docType['type'])->getFile();

        $this->recipients = $emailReport->emails()->pluck('email')->toArray();
        Mail::to($this->recipients)
            ->send(
                new ProjectReportEmailReport(
                    date('l\, m Y', strtotime($this->startAt)),
                    date('l\, m Y', strtotime($this->endAt ?? 'yesterday')),
                    $file,
                    $emailReport->name . $docType['fsType']
                )
            );

        if (Mail::failures()) {
            Log::alert('Unfortunately we wasn\'t able to send messages for this recipients: ' . print_r(Mail::failures()));
        }

        if (file_exists($file->getPathname())) {
            unlink($file->getPathname());
        }
    }
}
