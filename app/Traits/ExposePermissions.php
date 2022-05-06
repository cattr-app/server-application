<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

trait ExposePermissions
{
    protected function can(): Attribute
    {
        $model = $this;
        return new Attribute(
            get: static function() use ($model) {
                return collect($model::PERMISSIONS)
                    ->mapWithKeys(static fn($item) => [
                        $item => request()->user()->can(Str::camel($item), $model)
                    ]);
            }
        );
    }
}
