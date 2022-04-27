<?php

namespace App\Scopes;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Database\Query\Builder as QBuilder;

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
            ->orWhereHas('task.project.users', static fn(QBuilder $builder) => $builder
                ->where('projects_users.user_id', $user->id)
                ->where('projects_users.role_id', Role::getIdByName('manager')))
            ->orWhereHas('task.project.users', static fn(QBuilder $builder) => $builder
                ->where('projects_users.user_id', $user->id)
                ->where('projects_users.role_id', Role::getIdByName('auditor')));
    }
}
