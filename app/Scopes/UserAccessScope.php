<?php

namespace App\Scopes;

use App\Exceptions\Entities\AuthorizationException;
use App\Enums\Role;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Throwable;

class UserAccessScope implements Scope
{
    /**
     * @param Builder $builder
     * @param Model $model
     * @return Builder|null
     * @throws Throwable
     */
    public function apply(Builder $builder, Model $model): ?Builder
    {
        if (!auth()->hasUser()) {
            return null;
        }

        if (app()->runningInConsole()) {
            return $builder;
        }

        $user = optional(request())->user();

        throw_unless($user, new AuthorizationException);

        if ($user->hasRole([Role::ADMIN, Role::MANAGER, Role::AUDITOR])) {
            return $builder;
        }

        return $builder
            ->where('id', $user->id)
            ->orWhereHas('projectsRelation', static fn(Builder $builder) => $builder
                ->whereIn('project_id', static fn(Builder $builder) => $builder
                    ->from('projects_users')
                    ->select('project_id')
                    ->where(static fn(Builder $builder) => $builder
                        ->where('user_id', $user->id)
                        ->where('role_id', Role::MANAGER->value))
                    ->orWhere(static fn(Builder $builder) => $builder
                        ->where('user_id', $user->id)
                        ->where('role_id', Role::AUDITOR->value))));
    }
}
