<?php

namespace Modules\RedmineIntegration\Entities\Repositories;

use App\Models\Property;
use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserRepository
{
    /**
     * Returns user's redmine url saved in properties table
     *
     * @param $userId User's id in our system
     * @return string Redmine URL
     */
    public function getUserRedmineUrl(int $userId): string
    {
        $redmineUrlProperty = Property::where('entity_id', '=', $userId)
            ->where('entity_type', '=', Property::USER_CODE)
            ->where('name', '=', 'REDMINE_URL')->first();

        return $redmineUrlProperty ? $redmineUrlProperty->value : '';
    }

    /**
     * Returns user's redmine api key saved in properties table
     *
     * @param $userId User's id in our system
     * @return string Redmine URL
     */
    public function getUserRedmineApiKey(int $userId): string
    {
        $redmineApiKeyProperty = Property::where('entity_id', '=', $userId)
            ->where('entity_type', '=', Property::USER_CODE)
            ->where('name', '=', 'REDMINE_KEY')->first();

        return $redmineApiKeyProperty ? $redmineApiKeyProperty->value : '';
    }

    public function getUserRedmineTasks(int $userId): Collection
    {
        return DB::table(Property::getTableName() . ' as prop')
            ->select('prop.value as redmine_id', 'prop.entity_id as task_id')
            ->join(Task::getTableName() . ' as t', 'prop.entity_id', '=', 't.id')
            ->where('prop.entity_type', '=', Property::TASK_CODE)
            ->where('prop.name', '=', 'REDMINE_ID')
            ->where('t.user_id', '=', (int)$userId)->get();
    }
}