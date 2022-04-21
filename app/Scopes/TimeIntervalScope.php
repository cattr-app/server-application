<?php

namespace App\Scopes;

use App\Models\Role;
use App\Models\TimeInterval;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TimeIntervalScope implements Scope
{
    /**
     * @param Builder $builder
     * @param Model $model
     * @return Builder|null
     */
    public function apply(Builder $builder, Model $model): ?Builder
    {
        if (!auth()->hasUser()) {
            return null;
        }

        $user = request()->user();

        if ($user->hasRole('admin') || $user->hasRole('manager') || $user->hasRole('auditor')) {
            return $builder;
        }

        return $builder
            ->where('time_intervals.user_id', $user->id)
            ->orWhereHas('task.project.users', static fn(Builder $builder) => $builder
                ->where('projects_users.user_id', $user->id)
                ->where('projects_users.role_id', Role::getIdByName('manager')))
            ->orWhereHas('task.project.users', static fn(Builder $builder) => $builder
                ->where('projects_users.user_id', $user->id)
                ->where('projects_users.role_id', Role::getIdByName('auditor')));
    }
}
