<?php

namespace Modules\Invoices\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EmailReports
 * @package Modules\Invoices\Models
 * @mixin EloquentIdeHelper
 */
class Invoices extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'project_id',
        'rate',
    ];

    protected $table = 'invoices';

    public $incrementing = false;

    /**
     * @param  EloquentBuilder  $query
     *
     * @return EloquentBuilder
     */
    protected function setKeysForSaveQuery(Builder $query): EloquentBuilder
    {
        return $query->where('project_id', '=', $this->getAttribute('project_id'))
            ->where('user_id', '=', $this->getAttribute('user_id'));
    }
}
