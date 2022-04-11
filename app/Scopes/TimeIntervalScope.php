<?php

namespace App\Scopes;

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
     * @return Builder
     */
    public function apply(Builder $builder, Model $model)
    {
        /** @var User $user */
        $user = auth()->user();

        if (!$user) {
            return;
        }

        if ($user->hasRole('admin') || $user->hasRole('manager') || $user->hasRole('auditor')) {
            return $builder;
        }

        $timeIntervalTable = (new TimeInterval)->getTable();

        return $builder
            ->where("{$timeIntervalTable}.user_id", $user->id)
            ->orWhereHas('task.project.users', function (Builder $builder) use ($user) {
                $builder
                    ->where('projects_users.user_id', $user->id)
                    ->where('projects_users.role_id', 1);
            })
            ->orWhereHas('task.project.users', function (Builder $builder) use ($user) {
                $builder
                    ->where('projects_users.user_id', $user->id)
                    ->where('projects_users.role_id', 3);
            });
    }
}
