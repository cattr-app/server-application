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
     * user property name for select active status id
     */
    public const ACTIVE_STATUS_PROPERTY = 'REDMINE_ACTIVE_STATUS_ID';

    /**
     * user property name for select deactive status id
     */
    public const DEACTIVE_STATUS_PROPERTY = 'REDMINE_DEACTIVE_STATUS_ID';

    /**
     * user property name for select activate status ids
     */
    public const ACTIVATE_STATUSES_PROPERTY = 'REDMINE_ACTIVATE_STATUSES';

    /**
     * user property name for select deactivate status ids
     */
    public const DEACTIVATE_STATUSES_PROPERTY = 'REDMINE_DEACTIVATE_STATUSES';

    /**
     * user property name for select online timeout
     */
    public const ONLINE_TIMEOUT_PROPERTY = 'REDMINE_ONLINE_TIMEOUT';

    /**
     * Returns user's redmine url saved in properties table
     *
     * @param $userId  User's id in our system
     *
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
     * @param $userId  User's id in our system
     *
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
     * Returns user's redmine priorities saved in properties table
     *
     * @param $userId  User's id in our system
     *
     * @return array Redmine priorities
     */
    public function getUserRedminePriorities(int $userId): array
    {
        $redminePrioritiesProperty = Property::where('entity_id', '=', $userId)
            ->where('entity_type', '=', Property::USER_CODE)
            ->where('name', '=', 'REDMINE_PRIORITIES')->first();

        return $redminePrioritiesProperty ? (unserialize($redminePrioritiesProperty->value) ?: []) : [];
    }

    /**
     * Returns user's redmine tasks
     *
     * @param  int  $userId
     *
     * @return Collection
     */
    public function getUserRedmineTasks(int $userId): Collection
    {
        return DB::table(Property::getTableName().' as prop')
            ->select('prop.value as redmine_id', 'prop.entity_id as task_id')
            ->join(Task::getTableName().' as t', 'prop.entity_id', '=', 't.id')
            ->where('prop.entity_type', '=', Property::TASK_CODE)
            ->where('prop.name', '=', 'REDMINE_ID')
            ->where('t.user_id', '=', (int) $userId)->get();
    }

    /**
     * Returns user's new (unsynchronized) tasks
     *
     * @param  int  $userId
     *
     * @return Collection
     */
    public function getUserNewRedmineTasks(int $userId): Collection
    {
        return DB::table(Property::getTableName().' as prop')
            ->select(
                't.id',
                't.project_id',
                't.task_name',
                't.description',
                't.user_id',
                't.assigned_by',
                't.priority_id'
            )->join(Task::getTableName().' as t', 'prop.entity_id', '=', 't.id')
            ->where('prop.entity_type', '=', Property::TASK_CODE)
            ->where('prop.name', '=', 'NEW')
            ->where('prop.value', '=', '1')
            ->where('t.user_id', '=', (int) $userId)->get();
    }

    /**
     * Mark user with id == $userId as NEW
     *
     * Adds a specific row to properties table
     *
     * @param  int  $userId
     */
    public function markAsNew(int $userId)
    {
        $query = Property::where([
            'entity_id' => $userId,
            'entity_type' => Property::USER_CODE,
            'name' => 'NEW',
        ]);


        if ($query->exists()) {
            $query->update([
                'value' => 1
            ]);
        } else {
            Property::create([
                'entity_id' => $userId,
                'entity_type' => Property::USER_CODE,
                'name' => 'NEW',
                'value' => 1
            ]);
        }

    }

    /**
     * Mark user with id == $userId as NEW
     *
     * Adds a specific row to properties table
     *
     * @param  int  $userId
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
     *
     * @param  int  $userId
     *
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
     * @param  int  $userId         User local id
     * @param  int  $userRedmineId  User redmine id
     */
    public function setRedmineId(int $userId, int $userRedmineId)
    {
        Property::create([
            'entity_id' => $userId,
            'entity_type' => Property::USER_CODE,
            'name' => 'REDMINE_ID',
            'value' => $userRedmineId
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
     *
     * @return boolean
     */
    public function isUserSendTime($userId)
    {
        return Property::where([
            'entity_id' => $userId,
            'entity_type' => Property::USER_CODE,
            'name' => UserRepository::TIME_SEND_PROPERTY,
            'value' => '1',
        ])->exists();
    }


    /**
     * set user turned on (or not) redmine time sending
     *
     * @param $userId  integer
     * @param $enabled boolean
     */
    public function setUserSendTime($userId, $enabled)
    {

        $enabled = ($enabled) ? '1' : '0';


        $query = Property::where([
            'entity_id' => $userId,
            'entity_type' => Property::USER_CODE,
            'name' => UserRepository::TIME_SEND_PROPERTY,
        ]);


        if ($query->exists()) {
            $query->update([
                'value' => $enabled,
            ]);
        } else {
            Property::create([
                'entity_id' => $userId,
                'entity_type' => Property::USER_CODE,
                'name' => UserRepository::TIME_SEND_PROPERTY,
                'value' => $enabled
            ]);
        }
    }

    /**
     * get active task status id from userId
     *
     * @param  string  $userId
     *
     * @return string
     */
    public function getActiveStatusId($userId)
    {
        return $this->getProperty($userId, static::ACTIVE_STATUS_PROPERTY);
    }

    /**
     * get timeout for last task activity for user $userId
     *
     * @param  string  $userId
     * @param  string  $propertyName
     * @param  string  $retOnUnset  optional
     *
     * @return string
     */
    protected function getProperty($userId, string $propertyName, string $retOnUnset = '')
    {
        $property = Property::where([
            'entity_id' => $userId,
            'entity_type' => Property::USER_CODE,
            'name' => $propertyName,
        ])->first();

        if ($property) {
            return $property->value;
        }

        return $retOnUnset;
    }

    /**
     * set active task status id for userId
     *
     * @param  string  $userId
     * @param  string  $status_id
     *
     * @return string
     */
    public function setActiveStatusId($userId, $status_id)
    {
        return $this->setProperty($userId, static::ACTIVE_STATUS_PROPERTY, $status_id);
    }

    /**
     * set property for userId
     *
     * @param  string  $userId
     * @param  string  $propertyName
     * @param  mixed   $value
     */
    protected function setProperty($userId, string $propertyName, $value)
    {
        $params = [
            'entity_id' => $userId,
            'entity_type' => Property::USER_CODE,
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

    /**
     * get deactive task status id from userId
     *
     * @param  string  $userId
     *
     * @return string
     */
    public function getInactiveStatusId($userId)
    {
        return $this->getProperty($userId, static::DEACTIVE_STATUS_PROPERTY);
    }

    /**
     * set deactive task status id for userId
     *
     * @param  string  $userId
     * @param  string  $status_id
     *
     */
    public function setInactiveStatusId($userId, $status_id)
    {
        $this->setProperty($userId, static::DEACTIVE_STATUS_PROPERTY, $status_id);
    }

    /**
     * get user task status ids which will be set as active after task synchronisation
     *
     * @param  string  $userId
     *
     * @return string
     */
    public function getActivateStatuses($userId)
    {
        $jsonStatuses = $this->getProperty($userId, static::ACTIVATE_STATUSES_PROPERTY, '[]');
        return json_decode($jsonStatuses, 1);
    }

    /**
     * set user task status ids which will be set as active after task synchronisation
     *
     * @param  string  $userId
     * @param  array   $statuses
     *
     */
    public function setActivateStatuses($userId, array $statuses)
    {
        if (empty($statuses)) {
            $jsonStatuses = '';
        } else {
            $jsonStatuses = json_encode($statuses);
        }
        $this->setProperty($userId, static::ACTIVATE_STATUSES_PROPERTY, $jsonStatuses);
    }

    /**
     * get user task status ids which will be set as
     *
     * @param  string  $userId
     *
     * @return string
     */
    public function getDeactivateStatuses($userId)
    {
        $jsonStatuses = $this->getProperty($userId, static::DEACTIVATE_STATUSES_PROPERTY, '[]');
        return json_decode($jsonStatuses, 1);
    }

    /**
     * set user task status ids which will be set as inactive after task synchronisation
     *
     * @param  string  $userId
     * @param  array   $statuses
     *
     */
    public function setDeactivateStatuses($userId, array $statuses)
    {
        if (empty($statuses)) {
            $jsonStatuses = '';
        } else {
            $jsonStatuses = json_encode($statuses);
        }
        $this->setProperty($userId, static::DEACTIVATE_STATUSES_PROPERTY, $jsonStatuses);
    }

    /**
     * get timeout for last task activity for user $userId
     *
     * @param  string  $userId
     *
     * @return string
     */
    public function getOnlineTimeout($userId)
    {
        return $this->getProperty($userId, static::ONLINE_TIMEOUT_PROPERTY);
    }

    /**
     * set timeout $timeout for last task activity for user $userId
     *
     * @param  string  $userId
     * @param  int     $timeout
     */
    public function setOnlineTimeout($userId, $timeout)
    {
        $this->setProperty($userId, static::ONLINE_TIMEOUT_PROPERTY, $timeout);
    }

    /**
     * @param  int  $redmineId
     *
     * @return User
     */
    public function getUserByRedmineId(int $redmineId)
    {
        $userEav = Property::where([
            'entity_type' => Property::USER_CODE,
            'name' => 'REDMINE_ID',
            'value' => $redmineId
        ])->first();

        $userId = $userEav->entity_id;

        return User::find($userId);
    }
}
