<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class TimeDuration
 *
 * @property int $date
 * @property int $duration
 * @property int $user_id
 * @property User $user
 * @property int $id
 * @method static EloquentBuilder|TimeDuration whereDate($value)
 * @method static EloquentBuilder|TimeDuration whereDuration($value)
 * @method static EloquentBuilder|TimeDuration whereId($value)
 * @method static EloquentBuilder|TimeDuration whereUserId($value)
 * @mixin EloquentIdeHelper
 */
class TimeDuration extends Model
{
    /**
     * table name from database
     * @var string
     */
    protected $table = 'time_durations_cache';

    /**
     * @var array
     */
    protected $casts = [
        'duration' => 'integer',
        'user_id' => 'integer',
        'date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
