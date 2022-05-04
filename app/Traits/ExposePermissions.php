<?php

namespace App\Traits;

use Illuminate\Support\Str;

trait ExposePermissions
{
    /**
     * @return array
     */
    public function getCanAttribute(): array
    {
        $allowedPermissions = [];
        $permissions = $this->permissions ?? ['update', 'destroy'];

        foreach ($permissions as $permission) {
            $allowedPermissions[$permission] = request()->user()->can(Str::camel($permission), $this);
        }

        return $allowedPermissions;
    }
}
