<?php

namespace Modules\Reports\Exports;

use App\Helpers\ReportHelper;
use App\Models\Property;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ProjectExport implements Exportable
{
    const ROUND_DIGITS = 3;

    /**
     * @var ReportHelper
     */
    protected $reportHelper;

    /**
     * @var string
     */
    protected $timezone;

    public function __construct(ReportHelper $reportHelper)
    {
        $companyTimezoneProperty = Property::getProperty(Property::COMPANY_CODE, 'TIMEZONE')->first();
        $this->timezone = $companyTimezoneProperty ? $companyTimezoneProperty->getAttribute('value') : 'UTC';
        $this->reportHelper = $reportHelper;
    }

    /**
     * @return Collection
     * @throws Exception
     */
    public function collection(): Collection
    {
        $queryData = request()->only('start_at', 'end_at', 'uids', 'pids');
        if (!Arr::has($queryData, ['start_at', 'end_at', 'uids', 'pids'])) {
            throw new Exception('Requested data was not found in request body');
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

    /**
     * @param Collection $collection
     * @param string $projectName
     * @param array $user
     * @param array $task
     * @throws Exception
     */
    protected function addRowToCollection(Collection $collection, string $projectName, array $user, array $task)
    {
        $time = (new Carbon('@0'))->diffForHumans(new Carbon("@{$task['duration']}"), true, true, 3);
        $decimalTime = (new Carbon('@0'))->floatDiffInHours(new Carbon("@{$task['duration']}"));

        $collection->push([
            'Project' => $projectName,
            'User' => $user['full_name'],
            'Task' => $task['task_name'],
            'Time' => "{$time}",
            'Hours (decimal)' => round($decimalTime, self::ROUND_DIGITS)
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
     * @throws Exception
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
            'Hours (decimal)' => round($projectDecimalTime, self::ROUND_DIGITS)
        ]);
    }

    /**
     * Get processed, formatted and prepared-to-return collection
     *
     * @param  array  $collectionData
     *
     * @return Collection
     * @throws Exception
     */
    protected function getPreparedCollection(array $collectionData): Collection
    {
        $unprepCollection = $this->_getUnpreparedCollection($collectionData);
        return $this->getProcessedCollection($unprepCollection);
    }

    /**
     * Get unprocessed collection from database
     *
     * @param  array  $queryData
     *
     * @return Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    protected function _getUnpreparedCollection(array $queryData): Collection
    {
        $timezone = $this->timezone;
        $timezoneOffset = (new \Carbon\Carbon())->setTimezone($timezone)->format('P');

        $startAt = Carbon::parse($queryData['start_at'], $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $endAt = Carbon::parse($queryData['end_at'], $timezone)
            ->tz('UTC')
            ->toDateTimeString();

        $projectReportCollection = $this->reportHelper->getBaseQuery(
            $queryData['uids'], $startAt, $endAt, $timezoneOffset, [
            "JSON_ARRAYAGG(
                JSON_OBJECT(
                    'id', time_intervals.id, 'user_id', time_intervals.user_id, 'task_id', time_intervals.task_id,
                    'end_at', CONVERT_TZ(time_intervals.end_at, '+00:00', ?), 'start_at',
                    CONVERT_TZ(time_intervals.start_at, '+00:00', ?)
                )
            ) as intervals"
        ],
            [$timezoneOffset]
        )
            ->whereIn('project_id', $queryData['pids'])
            ->get();

        return $projectReportCollection;
    }

    public function getProcessedCollection($collection): Collection
    {
        $collection = $collection->groupBy('project_name');

        $resultCollection = [];
        foreach ($collection as $projectName => $items) {
            foreach ($items as $item) {
                $intervals = collect(json_decode($item->intervals, true));

                if (!array_key_exists($projectName, $resultCollection)) {
                    $resultCollection[$projectName] = [
                        'id' => $item->project_id,
                        'name' => $item->project_name,
                        'project_time' => 0,
                        'users' => [],
                    ];
                }
                if (!array_key_exists($item->user_id, $resultCollection[$projectName]['users'])) {
                    $resultCollection[$projectName]['users'][$item->user_id] = [
                        'id' => $item->user_id,
                        'full_name' => $item->user_name,
                        'tasks' => [],
                        'tasks_time' => 0,
                    ];
                }
                if (!array_key_exists($item->task_id,
                    $resultCollection[$projectName]['users'][$item->user_id]['tasks'])) {

                    $resultCollection[$projectName]['users'][$item->user_id]['tasks'][$item->task_id] = [
                        'task_name' => $item->task_name,
                        'id' => $item->task_id,
                        'duration' => 0,
                        'user_id' => $item->user_id
                    ];
                }

                foreach ($intervals as $interval) {
                    $duration = Carbon::parse($interval['end_at'])->diffInSeconds($interval['start_at']);

                    $resultCollection[$projectName]['users'][$item->user_id]['tasks'][$item->task_id]
                    ['duration'] += $duration;

                    $resultCollection[$projectName]['users'][$item->user_id]['tasks_time'] += $duration;
                    $resultCollection[$projectName]['project_time'] += $duration;
                }
            }
        }

        foreach ($resultCollection as &$project) {
            foreach ($project['users'] as &$user) {
                uasort($user['tasks'], function ($a, $b) {
                    return $a['duration'] < $b['duration'];
                });
            }

            uasort($project['users'], function ($a, $b) {
                return $a['tasks_time'] < $b['tasks_time'];
            });
        }

        uasort($resultCollection, function ($a, $b) {
            return $a['project_time'] < $b['project_time'];
        });

        return collect($resultCollection);
    }

    /**
     * @return string
     */
    public function getExporterName(): string
    {
        return 'projectReport';
    }
}
