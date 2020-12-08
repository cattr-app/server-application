<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ProjectScope implements Scope
{
    /**
     * @param Builder $builder
     * @param Model $model
     * @return Builder
     */
    public function apply(Builder $builder, Model $model): Builder
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (!$user || $user->hasRole('admin') || $user->hasRole('manager') || $user->hasRole('auditor')) {
            return $builder;
        }

        return $builder->whereHas('users', function (Builder $query) use ($user) {
            $query->where('user_id', $user->id);
        });
    }
}
