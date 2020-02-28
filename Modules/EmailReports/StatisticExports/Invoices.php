<?php

namespace Modules\EmailReports\StatisticExports;

use App\Helpers\ReportHelper;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
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
use Modules\Reports\Exports\InvoicesExport;
use Modules\Reports\Exports\ProjectExport;

/**
 * Class Invoices
 * @package Modules\EmailReports\StatisticExports
 */
class Invoices extends InvoicesExport implements ExportableEmailReport
{
    /**
     * @param array $queryData
     * @return Collection
     * @throws \Exception
     */
    public function exportCollection(array $queryData): Collection
    {
        if (!Arr::has($queryData, ['start_at', 'end_at', 'uids', 'pids'])) {
            Log::alert('We are missing some of data needed for query! class Invoices');
            return collect([]);
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
}
