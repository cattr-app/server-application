<?php

namespace App\Scopes;

use App\Exceptions\Entities\AuthorizationException;
use App\Enums\Role;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Throwable;

class TaskAccessScope implements Scope
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

        $user = optional(request())->user();

        throw_unless($user, new AuthorizationException);

        if (!$user || $user->hasRole([Role::ADMIN, Role::MANAGER, Role::AUDITOR])) {
            return $builder;
        }

        return $builder
            // A user with the user project role sees only their own tasks
            ->whereHas('users', static fn(Builder $builder) => $builder->where('id', $user->id))
            ->orWhereHas('project.users', static fn(Builder $builder) => $builder
                ->where('user_id', $user->id)
                ->whereIn(
                    'projects_users.role_id',
                    [
                        Role::MANAGER->value,
                        Role::USER->value,
                        Role::AUDITOR->value,
                    ],
                ))
            ->orderBy('created_at', 'desc');
    }
}
