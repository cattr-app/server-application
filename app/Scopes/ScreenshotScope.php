<?php

namespace App\Scopes;

use Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ScreenshotScope implements Scope
{
    /**
     * @param Builder $builder
     * @param Model $model
     * @return Builder
     */
    public function apply(Builder $builder, Model $model)
    {
        $user = auth()->user();

        if ($user->hasRole('admin') || $user->hasRole('manager') || $user->hasRole('auditor')) {
            return $builder;
        }

        return $builder
            ->whereHas('timeInterval', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->orWhereHas('timeInterval.task.project.users', function ($query) use ($user) {
                $query
                    ->where("projects_users.user_id", $user->id)
                    ->where("projects_users.role_id", 3);
            });
    }
}
