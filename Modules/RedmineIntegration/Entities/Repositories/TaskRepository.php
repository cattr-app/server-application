<?php

namespace Modules\RedmineIntegration\Entities\Repositories;

use App\Models\Property;

/**
 * Class TaskRepository
 */
class TaskRepository
{

    /**
     * user property name for checkbox "redmine status id"
     */
    public const STATUS_PROPERTY = 'REDMINE_STATUS_ID';


    /**
     * Returns Redmine ID for current task
     */
    public function getRedmineTaskId(int $taskId): ?string
    {
        $query = Property::where([
            ['entity_id', '=', $taskId],
            ['entity_type', '=', Property::TASK_CODE],
            ['name', '=', 'REDMINE_ID']
        ]);

        if (!$query->exists()) {
            return null;
        }
        return $query->first()->value;
    }

    /**
     * Mark task with id == $taskId as NEW
     *
     * Adds a specific row to properties table
     *
     * @param int $taskId
     */
    public function markAsNew(int $taskId): void
    {
        Property::create([
            'entity_id' => $taskId,
            'entity_type' => Property::TASK_CODE,
            'name' => 'NEW',
            'value' => 1
        ]);
    }

    /**
     * Mark task with id == $userId as NEW
     *
     * Adds a specific row to properties table
     *
     * @param int $taskId
     */
    public function markAsOld(int $taskId): void
    {
        Property::where('entity_id', '=', $taskId)
            ->where('entity_type', '=', Property::TASK_CODE)
            ->where('name', '=', 'NEW')
            ->update(['value' => 0]);

        Property::where([
            'enitity_id' => $taskId,
            'entity_type' => Property::TASK_CODE,
            'name' => 'NEW'
        ])->update(['value' => 0]);
    }

    /**
     * Set redmine id for task
     *
     * @param int $taskId Task id in local system
     * @param int $taskRedmineId Task id in redmine
     */
    public function setRedmineId(int $taskId, int $taskRedmineId): void
    {
        Property::create([
            'entity_id' => $taskId,
            'entity_type' => Property::TASK_CODE,
            'name' => 'REDMINE_ID',
            'value' => $taskRedmineId
        ]);
    }


    /**
     * get active task status id from taskId
     * @param $taskId
     * @return string
     */
    public function getRedmineStatusId($taskId): string
    {
        return $this->getProperty($taskId, static::STATUS_PROPERTY);
    }

    /**
     * set active task status id for taskId
     *
     * @param $taskId
     * @param $status_id
     */
    public function setRedmineStatusId($taskId, $status_id): void
    {
        $this->setProperty($taskId, static::STATUS_PROPERTY, $status_id);
    }

    /**
     * get property from taskId
     *
     * @param $taskId
     * @param string $propertyName
     * @param string $retOnUnset optional
     *
     * @return string
     */
    protected function getProperty($taskId, string $propertyName, string $retOnUnset = ''): string
    {
        $property = Property::where([
            'entity_id' => $taskId,
            'entity_type' => Property::TASK_CODE,
            'name' => $propertyName,
        ])->first();

        if ($property) {
            return $property->value;
        }

        return $retOnUnset;
    }

    /**
     * set property for taskId
     *
     * @param string $taskId
     * @param string $propertyName
     * @param mixed $value
     */
    protected function setProperty($taskId, string $propertyName, $value): void
    {
        $params = [
            'entity_id' => $taskId,
            'entity_type' => Property::TASK_CODE,
            'name' => $propertyName,
        ];

        $property = Property::where($params)->first();

        if (!$value) {
            $value = '';
        }

        if (!$property) {
            $params['value'] = $value;
            Property::create($params);
        } else {
            $property->value = $value;
            $property->save();
        }
    }
}
