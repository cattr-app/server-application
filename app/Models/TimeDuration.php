<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class TimeDuration
 * @package App\Models
 *
 * @property int $date
 * @property int $duration
 * @property int $user_id

 * @property User $user
 */
class TimeDuration extends AbstractModel
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

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
