<?php
namespace Modules\RedmineIntegration\Entities\Repositories;

use App\Models\Property;

/**
 * Class TaskRepository
 *
 * @package Modules\RedmineIntegration\Entities\Repositories
 */
class TaskRepository
{
    /**
     * Returns Redmine ID for current task
     * @param int $taskId
     * @return string
     */
    public function getRedmineTaskId(int $taskId)
    {
        $taskRedmineIdProperty = Property::where([
            ['entity_id', '=', $taskId],
            ['entity_type', '=', Property::TASK_CODE],
            ['name', '=', 'REDMINE_ID']
        ])->first();

        return $taskRedmineIdProperty->value;
    }

    /**
     * Mark task with id == $taskId as NEW
     *
     * Adds a specific row to properties table
     *
     * @param int $taskId
     */
    public function markAsNew(int $taskId)
    {
        Property::create([
            'entity_id'   => $taskId,
            'entity_type' => Property::TASK_CODE,
            'name'        => 'NEW',
            'value'       => 1
        ]);
    }

    /**
     * Mark task with id == $userId as NEW
     *
     * Adds a specific row to properties table
     *
     * @param int $taskId
     */
    public function markAsOld(int $taskId)
    {
        Property::where('entity_id', '=', $taskId)
            ->where('entity_type', '=', Property::TASK_CODE)
            ->where('name', '=', 'NEW')
            ->update(['value' => 0]);
    }

    public function setRedmineId($taskId, $taskRedmineId)
    {
        Property::create([
            'entity_id'   => $taskId,
            'entity_type' => Property::TASK_CODE,
            'name'        => 'REDMINE_ID',
            'value'       => $taskRedmineId
        ]);
    }
}