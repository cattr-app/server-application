<?php

namespace App\Scopes;

use App\Exceptions\Entities\AuthorizationException;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Throwable;
use Illuminate\Database\Query\Builder as QBuilder;

class ScreenshotScope implements Scope
{
    /**
     * @param Builder $builder
     * @param Model $model
     * @return Builder
     * @throws Throwable
     */
    public function apply(Builder $builder, Model $model): Builder
    {
        if (app()->runningInConsole()) {
            return $builder;
        }

        $user = request()->user();

        throw_unless($user, new AuthorizationException);

        if ($user->hasRole('admin') || $user->hasRole('manager') || $user->hasRole('auditor')) {
            return $builder;
        }

        return $builder
            ->whereHas('timeInterval', static fn(QBuilder $query) => $query->where('user_id', $user->id))
            ->orWhereHas('timeInterval.task.project.users', static fn(QBuilder $query) => $query
                ->where('projects_users.user_id', $user->id)
                ->where('projects_users.role_id', Role::getIdByName('auditor')));
    }
}
