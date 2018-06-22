<?php

namespace Modules\RedmineIntegration\Entities\Repositories;

use App\Models\Property;
use Illuminate\Support\Facades\DB;

/**
 * Class ProjectRepository
 *
 * @package Modules\RedmineIntegration\Entities\Repositories
 */
class ProjectRepository
{
    /**
     * Returns redmine id for current project
     *
     * @param $projectId
     * @return mixed|string
     */
    public function getRedmineProjectId($projectId)
    {
        $projectRedmineIdProperty = Property::where([
            ['entity_id', '=', $projectId],
            ['entity_type', '=', Property::PROJECT_CODE],
            ['name', '=', 'REDMINE_ID']
        ])->first();

        return $projectRedmineIdProperty->value;
    }

    /**
     * Returns all redmine project's ids
     *
     * @return array
     */
    public function getRedmineProjectsIds()
    {
        $redmineProjectIdsArray = [];

        $redmineProjectsCollection = DB::table(Property::getTableName() . ' as prop')
            ->select('prop.entity_id')
            ->where('prop.entity_type', '=', Property::PROJECT_CODE)
            ->where('prop.name', '=', 'REDMINE_ID')->get();

        foreach ($redmineProjectsCollection as $redmineProject) {
            $redmineProjectIdsArray[] = $redmineProject->entity_id;
        }

        return $redmineProjectIdsArray;
    }
}