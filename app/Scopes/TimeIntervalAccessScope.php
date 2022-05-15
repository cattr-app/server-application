<?php

namespace App\Scopes;

use App\Exceptions\Entities\AuthorizationException;
use App\Models\Role;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Throwable;

class TimeIntervalAccessScope implements Scope
{
    /**
     * @param Builder $builder
     * @param Model $model
     * @return Builder|null
     * @throws Throwable
     */
    public function apply(Builder $builder, Model $model): ?Builder
    {
        if (app()->runningInConsole()) {
            return $builder;
        }

        $user = optional(request())->user();

        throw_unless($user, new AuthorizationException);

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
