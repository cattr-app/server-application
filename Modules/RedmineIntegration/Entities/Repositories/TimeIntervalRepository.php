<?php

namespace Modules\RedmineIntegration\Entities\Repositories;

use App\Models\Property;
use App\Models\TimeInterval;
use Illuminate\Database\Eloquent\Builder;

class TimeIntervalRepository
{
    public const REDMINE_SYNCED_PROPERTY = 'REDMINE_SYNCED';

    public function getNotSyncedIntervals($userId): Builder
    {
        return TimeInterval::query()
            ->where('user_id', $userId)
            ->where(static function ($query) {
                $query
                    ->whereHas('properties', static function ($propertyQuery) {
                        $propertyQuery->where('name', '=', static::REDMINE_SYNCED_PROPERTY);
                        $propertyQuery->where('value', '!=', 1);
                    })
                    ->orWhere(static function ($propertyQuery) {
                        $propertyQuery->whereDoesntHave('properties', static function ($hasntQuery) {
                            $hasntQuery->where('name', '=', static::REDMINE_SYNCED_PROPERTY);
                        });
                    });
            });
    }

    public function markAsSynced(int $intervalId): void
    {
        $query = Property::where([
            'entity_id' => $intervalId,
            'entity_type' => Property::TIME_INTERVAL_CODE,
            'name' => static::REDMINE_SYNCED_PROPERTY,
        ]);

        if ($query->exists()) {
            $query->update(['value' => 1]);
        } else {
            Property::create([
                'entity_id' => $intervalId,
                'entity_type' => Property::TIME_INTERVAL_CODE,
                'name' => static::REDMINE_SYNCED_PROPERTY,
                'value' => 1
            ]);
        }
    }
}

