<?php

namespace Modules\EmailReports\StatisticExports;

use App\Helpers\ReportHelper;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Mail;
use Modules\EmailReports\Mail\InvoicesEmailReport;
use Modules\EmailReports\Models\EmailReports;
use Modules\Invoices\Models\Invoices as InvoiceModel;
use Modules\Invoices\Models\UserDefaultRate;
use Modules\Reports\Exports\ProjectExport;

class Invoices extends ProjectExport implements FromCollection, ShouldAutoSize
{
    /**
     * @var ReportHelper
     */
    public $reportHelper;

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
     * This field needed because of overriding method (method suppose to have same number of arguments)
     * @var int
     */
    private $summaryForUserTask;

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
        $preparedCollection = $this->getPreparedCollection($queryData);

        $returnableData = collect([]);
        $totalTime = 0;
        $totalSummary = 0;

        // Processing grouped database collection to fill the collection, which will be exported
        foreach ($preparedCollection as $projectName => $project) {
            $totalTasksTime = 0;
            foreach ($project['users'] as $user) {
                $this->summaryForUserTask = 0;
                $defaultRate = $this->getUserDefaultRate($user['id']);
                $uniqueProjectRate = $this->getUserRateForProject($user['id'], $project['id']);
                $user += [
                    'rate' => floatval($uniqueProjectRate ?? $defaultRate)
                ];
                foreach ($user['tasks'] as $task) {
                    $this->addRowToCollection($returnableData, $projectName, $user, $task);
                }
                $totalTasksTime += $user['tasks_time'];
                $decimalTime = (new Carbon('@0'))->floatDiffInHours(new Carbon("@{$totalTasksTime}"));
                $this->summaryForUserTask += round($user['rate'] * $decimalTime, self::ROUND_DIGITS);
                $totalSummary += $this->summaryForUserTask;
                $this->addSubtotalToCollection($returnableData, $projectName, $totalTasksTime);
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
            'Hours (decimal)' => round($totalTime, self::ROUND_DIGITS),
            'Summary' => round($totalSummary, self::ROUND_DIGITS)
        ]);

        return $returnableData;
    }

    /**
     * @param Collection $collection
     * @param string $projectName
     * @param array $user
     * @param array $task
     * @throws \Exception
     */
    protected function addRowToCollection(Collection $collection, string $projectName, array $user, array $task)
    {
        $time = (new Carbon('@0'))->diffForHumans(new Carbon("@{$task['duration']}"), true, true, 3);
        $decimalTime = (new Carbon('@0'))->floatDiffInHours(new Carbon("@{$task['duration']}"));
        $summaryPerTask = round($user['rate'] * $decimalTime, self::ROUND_DIGITS);

        $collection->push([
            'Project' => $projectName,
            'User' => $user['full_name'],
            'Task' => $task['task_name'],
            'Time' => "{$time}",
            'Hours (decimal)' => round($decimalTime, self::ROUND_DIGITS),
            'Rate' => $user['rate'],
            'Summary' => $summaryPerTask
        ]);
    }

    /**
     * Add subtotal record to existing collection
     *
     * @param Collection $collection
     * @param string $projectName
     * @param int|float $time
     *
     * @return void
     * @throws \Exception
     */
    protected function addSubtotalToCollection(Collection $collection, string $projectName, $time): void
    {
        $timeObject = (new Carbon('@0'))->diffForHumans(new Carbon("@$time"),true, true, 3);
        $projectDecimalTime = (new Carbon('@0'))->floatDiffInHours(new Carbon("@$time"));

        $collection->push([
            'Project' => "Subtotal for $projectName",
            'User' => '',
            'Task' => '',
            'Time' => "{$timeObject}",
            'Hours (decimal)' => round($projectDecimalTime, self::ROUND_DIGITS),
            'Summary' => $this->summaryForUserTask
        ]);
    }

    protected function getUserDefaultRate($userId)
    {
        $defaultRate = UserDefaultRate::where('user_id', '=', $userId)->first();
        return $defaultRate ? $defaultRate->default_rate : UserDefaultRate::ZERO_RATE;
    }

    protected function getUserRateForProject(int $userId, int $projectId)
    {
        $userRateForProjects =  InvoiceModel::where('user_id', $userId)->where('project_id', $projectId)->first();
        return $userRateForProjects->rate ?? null;
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
                new InvoicesEmailReport(
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
