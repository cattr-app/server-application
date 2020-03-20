<?php

namespace Modules\Invoices\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin EloquentIdeHelper
 */
class UserDefaultRate extends Model
{
    public const ZERO_RATE = 0;

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'default_rate',
    ];
    protected $table = 'user_default_rate';

    protected function setKeysForSaveQuery(Builder $query): EloquentBuilder
    {
        return $query->where('user_id', '=', $this->getAttribute('user_id'));
    }
}
