<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TimeInterval
 * @package App\Models
 *
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property string $start_at
 * @property string $end_at
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 *
 * @property Task $task
 * @property User $user
 * @property Screenshot[] $screenshots
 */
class TimeInterval extends Model
{
    use SoftDeletes;

	/**
     * table name from database
     * @var string
     */
    protected $table = 'time_intervals';

    /**
     * @var array
     */
    protected $fillable = ['task_id', 'start_at', 'user_id', 'end_at', 'count_mouse', 'count_keyboard'];

    /**
     * @return BelongsTo
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany
     */
    public function screenshots(): HasMany
    {
    	return $this->hasMany(Screenshot::class, 'time_interval_id');
    }
}
