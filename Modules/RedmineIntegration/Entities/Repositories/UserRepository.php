<?php

namespace Modules\RedmineIntegration\Entities\Repositories;

use App\Models\Property;
use App\Models\Task;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class UserRepository
 *
 * @package Modules\RedmineIntegration\Entities\Repositories
 */
class UserRepository
{

    /**
     * user property name for checkbox "send time in redmine"
     */
    public const TIME_SEND_PROPERTY = 'REDMINE_SEND_TIME';

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

    /**
     * Returns user's redmine statuses saved in properties table
     *
     * @param $userId User's id in our system
     * @return array Redmine statuses
     */
    public function getUserRedmineStatuses(int $userId): array
    {
        $redmineStatusesProperty = Property::where('entity_id', '=', $userId)
            ->where('entity_type', '=', Property::USER_CODE)
            ->where('name', '=', 'REDMINE_STATUSES')->first();

        return $redmineStatusesProperty ? unserialize($redmineStatusesProperty->value) : [];
    }

    /**
     * Returns user's redmine priorities saved in properties table
     *
     * @param $userId User's id in our system
     * @return array Redmine priorities
     */
    public function getUserRedminePriorities(int $userId): array
    {
        $redminePrioritiesProperty = Property::where('entity_id', '=', $userId)
            ->where('entity_type', '=', Property::USER_CODE)
            ->where('name', '=', 'REDMINE_PRIORITIES')->first();

        return $redminePrioritiesProperty ? unserialize($redminePrioritiesProperty->value) : [];
    }

    /**
     * Returns user's redmine tasks
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserRedmineTasks(int $userId): Collection
    {
        return DB::table(Property::getTableName() . ' as prop')
            ->select('prop.value as redmine_id', 'prop.entity_id as task_id')
            ->join(Task::getTableName() . ' as t', 'prop.entity_id', '=', 't.id')
            ->where('prop.entity_type', '=', Property::TASK_CODE)
            ->where('prop.name', '=', 'REDMINE_ID')
            ->where('t.user_id', '=', (int)$userId)->get();
    }

    /**
     * Returns user's new (unsynchronized) tasks
     *
     * @param int $userId
     * @return Collection
     */
    public function getUserNewRedmineTasks(int $userId): Collection
    {
        return DB::table(Property::getTableName() . ' as prop')
            ->select(
                't.id',
                't.project_id',
                't.task_name',
                't.description',
                't.user_id',
                't.assigned_by',
                't.priority_id'
            )->join(Task::getTableName() . ' as t', 'prop.entity_id', '=', 't.id')
            ->where('prop.entity_type', '=', Property::TASK_CODE)
            ->where('prop.name', '=', 'NEW')
            ->where('prop.value', '=', '1')
            ->where('t.user_id', '=', (int)$userId)->get();
    }

    /**
     * Mark user with id == $userId as NEW
     *
     * Adds a specific row to properties table
     *
     * @param int $userId
     */
    public function markAsNew(int $userId)
    {
        $query = Property::where([
            'entity_id'   => $userId,
            'entity_type' => Property::USER_CODE,
            'name'        => 'NEW',
        ]);


        if ($query->exists()) {
            $query->update([
                'value'       => 1
            ]);
        } else {
            Property::create([
                'entity_id'   => $userId,
                'entity_type' => Property::USER_CODE,
                'name'        => 'NEW',
                'value'       => 1
            ]);
        }

    }

    /**
     * Mark user with id == $userId as NEW
     *
     * Adds a specific row to properties table
     *
     * @param int $userId
     */
    public function markAsOld(int $userId)
    {
        Property::where('entity_id', '=', $userId)
            ->where('entity_type', '=', Property::USER_CODE)
            ->where('name', '=', 'NEW')
            ->update(['value' => 0]);
    }

    /**
     * Returns redmine id for current user
     * @param int $userId
     * @return string
     */
    public function getUserRedmineId(int $userId): string
    {
        $userRedmineId = Property::where('entity_id', '=', $userId)
            ->where('entity_type', '=', Property::USER_CODE)
            ->where('name', '=', 'REDMINE_ID')->first();

        return $userRedmineId ? $userRedmineId->value : '';
    }

    /**
     * Set redmine id for user
     *
     * @param int $userId User local id
     * @param int $userRedmineId User redmine id
     */
    public function setRedmineId(int $userId, int $userRedmineId)
    {
        Property::create([
            'entity_id'   => $userId,
            'entity_type' => Property::USER_CODE,
            'name'        => 'REDMINE_ID',
            'value'       => $userRedmineId
        ]);
    }

    /**
     * Returns new users, who turn on redmine intergration
     *
     * Add special row to properties table
     *
     * @return Collection
     */
    public function getNewRedmineUsers()
    {
        return Property::where('entity_type', '=', Property::USER_CODE)
            ->where('name', '=', 'NEW')
            ->where('value', '=', '1')->get();
    }

    /**
     * Returns users, who has turned on redmine time sending
     *
     * @return Collection
     */
    public function getSendTimeUsers()
    {
        return User::query()
        ->whereHas('properties', function ($propertyQuery) {
            $propertyQuery->where('name', '=', UserRepository::TIME_SEND_PROPERTY);
            $propertyQuery->where('value', '=', '1');
        })
        ->get();
    }


    /**
     * Check is user has turned on redmine time sending
     *
     * @param $userId integer
     * @return boolean
     */
    public function isUserSendTime($userId)
    {
        return Property::where([
            'entity_id'     => $userId,
            'entity_type'   => Property::USER_CODE,
            'name'          => UserRepository::TIME_SEND_PROPERTY,
            'value'         => '1',
        ])->exists();
    }


    /**
     * set user turned on (or not) redmine time sending
     *
     * @param $userId integer
     * @param $enabled boolean
     */
    public function setUserSendTime($userId, $enabled)
    {

        $enabled = ($enabled) ? '1' : '0';


        $query = Property::where([
            'entity_id'     => $userId,
            'entity_type'   => Property::USER_CODE,
            'name'          => UserRepository::TIME_SEND_PROPERTY,
        ]);


        if ($query->exists()) {
            $query->update([
                'value' => $enabled,
            ]);
        } else {
            Property::create([
                'entity_id'   => $userId,
                'entity_type' => Property::USER_CODE,
                'name'        => UserRepository::TIME_SEND_PROPERTY,
                'value'       => $enabled
            ]);
        }
    }

}