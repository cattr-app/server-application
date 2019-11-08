<?php

namespace Modules\Reports\Exports;

use App\Models\Project;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Modules\Reports\Entities\ProjectReport;

class ProjectExport implements FromCollection, WithEvents
{
    const ROUND_DIGITS = 3;

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
        $preparedCollection->each(function ($item, $key) use ($returnableData) {
            $item->each(function ($user) use ($item, $key, $returnableData) {
                $user = $user[0];
                foreach ($user['tasks'] as $task) {
                    $this->addRowToCollection($returnableData, $key, $user, $task);
                }

                $this->addSubtotalToCollection($returnableData, $key, $user['tasks_time']);
            });
        });

        // Calculating total time
        foreach ($preparedCollection as $item) {
            foreach ($item as $user) {
                $user = $user[0];
                $totalTime += $user['tasks_time'];
            }
        }

        // Add total row to collection
        $time = (new Carbon('@0'))->diff(new Carbon("@$totalTime"));
        $totalTime = round($totalTime / 60 / 60, static::ROUND_DIGITS);

        $returnableData->push([
            'Project' => '',
            'User' => '',
            'Task' => 'Total',
            'Time' => "{$time->h}:{$time->i}:{$time->s}",
            'Hours (decimal)' => $totalTime
        ]);

        return $returnableData;
    }

    /**
     * @param  Collection  $collection
     * @param  string      $projectName
     * @param  array       $user
     * @param  array       $task
     */
    protected function addRowToCollection(Collection $collection, string $projectName, array $user, array $task)
    {
        $time = (new Carbon('@0'))->diff(new Carbon("@{$task['duration']}"));
        $decimalTime = round($task['duration'] / 60 / 60, static::ROUND_DIGITS);

        $collection->push([
            'Project' => $projectName,
            'User' => $user['full_name'],
            'Task' => $task['task_name'],
            'Time' => "{$time->h}:{$time->i}:{$time->s}",
            'Hours (decimal)' => $decimalTime
        ]);
    }

    /**
     * Add subtotal record to existing collection
     *
     * @param  Collection  $collection
     * @param  string      $projectName
     * @param  int|float   $time
     *
     * @return void
     */
    protected function addSubtotalToCollection(Collection $collection, string $projectName, $time): void
    {
        $timeObject = (new Carbon('@0'))->diff(new Carbon("@$time"));
        $projectDecimalTime =round($time / 60 / 60, static::ROUND_DIGITS);

        $collection->push([
            'Project' => "Subtotal for $projectName",
            'User' => '',
            'Task' => '',
            'Time' => "{$timeObject->h}:{$timeObject->i}:{$timeObject->s}",
            'Hours (decimal)' => $projectDecimalTime
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

        return $this->prepareCollection($unprepCollection);
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
        /** @noinspection PhpParamsInspection */
        return ProjectReport::query()->select(
            'user_id',
            'user_name',
            'task_id',
            'project_id',
            'task_name',
            'project_name',
            DB::raw('SUM(duration) as duration')
        )
            ->whereIn('user_id', $queryData['uids'])
            ->whereIn('project_id', $queryData['pids'])
            ->whereIn('project_id', Project::getUserRelatedProjectIds(auth()->user()))
            ->where('date', '>=', $queryData['start_at'])
            ->where('date', '<', $queryData['end_at'])
            ->groupBy('user_id', 'task_id', 'project_id')
            ->get();

    }

    /**
     * Preparing returnable collection for "collect" method
     *
     * @param  Collection  $collection
     *
     * @return Collection
     */
    protected function prepareCollection(Collection $collection): Collection
    {
        $plainData = [];

        foreach ($collection as $item) {
            $this->_preparePlainData($item, $plainData, $item->user_id, $item->project_id);
        }

        return collect($this->processPlainData($plainData));
    }

    /**
     * After we'll got an workable plain data, we'll need to reformat and process it
     *
     * @param  array  $plainData
     *
     * @return array
     */
    protected function processPlainData(array $plainData): array
    {
        foreach ($plainData as $id => $project) {
            $plainData[$id]['users'] = array_values($project['users']);
        }

        return array_values($plainData);
    }

    /**
     * Here we'll need to format plain database data to workable format
     *
     * @param         $item
     * @param  array  $plainData
     * @param         $userId
     * @param         $projectId
     *
     * @return void
     */
    protected function _preparePlainData($item, array &$plainData, $userId, $projectId): void
    {
        if (!isset($plainData[$projectId])) {
            $plainData[$projectId] = [
                'id' => $projectId,
                'name' => $item->project_name,
                'users' => [],
                'project_time' => 0
            ];
        }

        if (!isset($plainData[$projectId]['users'][$userId])) {
            $plainData[$projectId]['users'][$userId] = [
                'id' => $userId,
                'full_name' => $item->user_name,
                'tasks' => [],
                'tasks_time' => 0
            ];
        }

        $plainData[$projectId]['users'][$userId]['tasks'][] = [
            'id' => $item->task_id,
            'project_id' => $item->project_id,
            'user_id' => $item->user_id,
            'task_name' => $item->task_name,
            'duration' => (int) $item->duration,
        ];

        $plainData[$projectId]['users'][$userId]['tasks_time'] += $item->duration;
        $plainData[$projectId]['project_time'] += $item->duration;
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $headers = 'A1:W1';
                $event->sheet->getDelegate()->getStyle($headers)->getFont()->setBold(true);

                $event->sheet->getDelegate()->getColumnDimension('A1:A9999')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('B1:B9999')->setWidth(10);
                $event->sheet->getDelegate()->getColumnDimension('C1:C9999')->setWidth(20);
                $event->sheet->getDelegate()->getColumnDimension('E1:E9999')->setWidth(15);
            }
        ];
    }
}
