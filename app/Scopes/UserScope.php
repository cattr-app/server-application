<?php

namespace App\Scopes;

use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class UserScope implements Scope
{
    /**
     * @param Builder $builder
     * @param Model $model
     * @return Builder
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
            ->where('id', $user->id)
            ->orWhereHas('projectsRelation', static fn(Builder $builder) => $builder
                ->whereIn('project_id', static fn(Builder $builder) => $builder
                    ->from('projects_users')
                    ->select('project_id')
                    ->where(static fn(Builder $builder) => $builder
                        ->where('user_id', $user->id)
                        ->where('role_id', Role::getIdByName('manager')))
                    ->orWhere(static fn(Builder $builder) => $builder
                        ->where('user_id', $user->id)
                        ->where('role_id', Role::getIdByName('auditor')))));
    }
}
