<?php

namespace Modules\RedmineIntegration\Entities\Repositories;

use App\Models\Property;
use App\Models\TimeInterval;

/**
 * Class TimeIntervalRepository
 *
 * @package Modules\RedmineIntegration\Entities\Repositories
 */
class TimeIntervalRepository
{

    public const REDMINE_SYNCED_PROPERTY = 'REDMINE_SYNCED';

    public function getNotSyncedInvervals($userId)
    {
        return TimeInterval::query()
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query
                    ->whereHas('properties', function ($propertyQuery) {
                        $propertyQuery->where('name', '=', static::REDMINE_SYNCED_PROPERTY);
                        $propertyQuery->where('value', '!=', 1);
                    })
                    ->orWhere(function ($propertyQuery) {
                        $propertyQuery->whereDoesntHave('properties', function ($hasntQuery) {
                            $hasntQuery->where('name', '=', static::REDMINE_SYNCED_PROPERTY);
                        });
                    });
            });
    }

    public function markAsSynced(int $timeinterval_id)
    {
        $query = Property::where([
            'entity_id' => $timeinterval_id,
            'entity_type' => Property::TIME_INTERVAL_CODE,
            'name' => static::REDMINE_SYNCED_PROPERTY,
        ]);

        if ($query->exists()) {
            $query->update(['value' => 1]);
        } else {
            Property::create([
                'entity_id' => $timeinterval_id,
                'entity_type' => Property::TIME_INTERVAL_CODE,
                'name' => static::REDMINE_SYNCED_PROPERTY,
                'value' => 1
            ]);
        }
    }
}

