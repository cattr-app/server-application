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
            if (auth()->check() && auth()->user()->can(Str::camel($permission), $this)) {
                $allowedPermissions[$permission] = true;
            } else {
                $allowedPermissions[$permission] = false;
            }
        }

        return $allowedPermissions;
    }
}
