<?php

namespace Modules\EmailReports\StatisticExports;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Reports\Exports\ProjectExport;

class ProjectReport extends ProjectExport implements ExportableEmailReport
{
    /**
     * @throws Exception
     */
    public function exportCollection(array $queryData): Collection
    {
        if (!Arr::has($queryData, ['start_at', 'end_at', 'uids', 'pids'])) {
            Log::error('We are missing some of data needed for query! class Invoices');
            return collect([]);
        }

        // Grouping prepared collection to groups by Project Name
        $preparedCollection = $this->getPreparedCollection($queryData)->mapToGroups(static function ($item) {
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
}
