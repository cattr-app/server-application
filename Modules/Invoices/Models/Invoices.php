<?php

namespace Modules\Invoices\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin EloquentIdeHelper
 */
class Invoices extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'project_id',
        'rate',
    ];
    protected $table = 'invoices';

    protected function setKeysForSaveQuery(Builder $query): EloquentBuilder
    {
        return $query->where('project_id', '=', $this->getAttribute('project_id'))
            ->where('user_id', '=', $this->getAttribute('user_id'));
    }
}
