<?php

namespace Modules\Invoices\Models;

use Eloquent as EloquentIdeHelper;
use App\Models\AbstractModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * Class EmailReports
 * @package Modules\Invoices\Models
 * @mixin EloquentIdeHelper
 */
class UserDefaultRate extends AbstractModel
{
    public const ZERO_RATE = 0;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'default_rate',
    ];

    protected $table = 'user_default_rate';

    public $incrementing = false;

    /**
     * @param  EloquentBuilder  $query
     *
     * @return EloquentBuilder
     */
    protected function setKeysForSaveQuery(Builder $query): EloquentBuilder
    {
        return $query->where('user_id', '=', $this->getAttribute('user_id'));
    }
}
