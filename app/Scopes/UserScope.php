<?php

namespace App\Scopes;

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
    public function apply(Builder $builder, Model $model)
    {
        if (!auth()->hasUser()) {
            return;
        }

        $user = auth()->user();

        if ($user->hasRole('admin') || $user->hasRole('manager') || $user->hasRole('auditor')) {
            return $builder;
        }

        return $builder
            ->where('id', $user->id)
            ->orWhereHas('projectsRelation', function (Builder $builder) use ($user) {
                $builder
                    ->whereIn('project_id', function ($builder) use ($user) {
                        $builder
                            ->from('projects_users')
                            ->select('project_id')
                            ->where(function ($builder) use ($user) {
                                $builder
                                    ->where('user_id', $user->id)
                                    ->where('role_id', 1);
                            })
                            ->orWhere(function ($builder) use ($user) {
                                $builder
                                    ->where('user_id', $user->id)
                                    ->where('role_id', 3);
                            });
                    });
            });
    }
}
