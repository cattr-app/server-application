<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

trait ExposePermissions
{
    protected ?User $permissionsUser = null;

    /** Overrides the user for which the list of permissions is shown in the can attribute. */
    public function setPermissionsUser(?User $user): self
    {
        $this->permissionsUser = $user;
        return $this;
    }

    protected function can(): Attribute
    {
        $model = $this;
        return Attribute::make(
            get: static function () use ($model) {
                $user = $model->permissionsUser ?? request()->user(); // if called from queue - use existing user
                return collect($model::PERMISSIONS)->mapWithKeys(static fn ($item) => [
                    $item => $user?->can(Str::camel($item), $model)
                ]);
            }
        )->shouldCache();
    }
}
