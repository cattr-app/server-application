<?php
namespace Modules\RedmineIntegration\Entities\Repositories;

use App\Models\Property;

class ProjectRepository
{
    public function getRedmineProjectId($projectId)
    {
        $projectRedmineIdProperty = Property::where([
            ['entity_id', '=', $projectId],
            ['entity_type', '=', Property::PROJECT_CODE],
            ['name', '=', 'REDMINE_ID']
        ])->first();

        return $projectRedmineIdProperty->value;
    }
}