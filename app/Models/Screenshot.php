<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Screenshot
 * @package App\Models
 *
 * @property int $id
 * @property int $time_interval_id
 * @property string $path
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 *
 * @property TimeInterval $timeInterval
 */
class Screenshot extends Model
{
    use SoftDeletes;

    /**
     * table name from database
     * @var string
     */
    protected $table = 'screenshots';

    /**
     * @var array
     */
    protected $fillable = ['time_interval_id', 'path'];

    /**
     * @return BelongsTo
     */
    public function timeInterval(): BelongsTo
    {
        return $this->belongsTo(TimeInterval::class, 'time_interval_id');
    }
}
