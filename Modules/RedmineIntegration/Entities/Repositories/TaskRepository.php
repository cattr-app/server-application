<?php
namespace Modules\RedmineIntegration\Entities\Repositories;

use App\Models\Property;

class TaskRepository
{
    public function getRedmineTaskId($taskId)
    {
        $taskRedmineIdProperty = Property::where([
            ['entity_id', '=', $taskId],
            ['entity_type', '=', Property::TASK_CODE],
            ['name', '=', 'REDMINE_ID']
        ])->first();

        return $taskRedmineIdProperty->value;
    }
}