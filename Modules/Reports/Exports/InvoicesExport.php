<?php

namespace Modules\Reports\Exports;

use App\Helpers\ReportHelper;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Modules\Invoices\Models\Invoices as InvoiceModel;
use Modules\Invoices\Models\UserDefaultRate;

/**
 * Class Invoices
 * @package Modules\EmailReports\StatisticExports
 */
class InvoicesExport extends ProjectExport
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
    public $recipients;

    /**
     * This field needed because of overriding method (method suppose to have same number of arguments)
     * @var int
     */
    protected $summaryForUserTask;

    /**
     * @return Collection
     * @throws \Exception
     */
    public function collection(): Collection
    {
        $queryData = request()->only('start_at', 'end_at', 'uids', 'pids');
        if (!Arr::has($queryData, ['start_at', 'end_at', 'uids', 'pids'])) {
            throw new \Exception('Requested data was not found in request body');
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
        $this->summaryForUserTask = round($user['rate'] * $decimalTime, self::ROUND_DIGITS);

        $collection->push([
            'Project' => $projectName,
            'User' => $user['full_name'],
            'Task' => $task['task_name'],
            'Time' => "{$time}",
            'Hours (decimal)' => round($decimalTime, self::ROUND_DIGITS),
            'Rate' => $user['rate'],
            'Summary' => $this->summaryForUserTask
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
        $timeObject = (new Carbon('@0'))->diffForHumans(new Carbon("@$time"), true, true, 3);
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

    /**
     * @param $userId
     * @return int|mixed
     */
    protected function getUserDefaultRate($userId)
    {
        $defaultRate = UserDefaultRate::where('user_id', '=', $userId)->first();
        return $defaultRate ? $defaultRate->default_rate : UserDefaultRate::ZERO_RATE;
    }

    /**
     * @param int $userId
     * @param int $projectId
     * @return mixed|null
     */
    protected function getUserRateForProject(int $userId, int $projectId)
    {
        $userRateForProjects = InvoiceModel::where('user_id', $userId)->where('project_id', $projectId)->first();
        return $userRateForProjects->rate ?? null;
    }

    /**
     * @return string
     */
    public function getExporterName(): string
    {
        return "invoices";
    }
}
