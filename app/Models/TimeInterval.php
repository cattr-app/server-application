<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

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
class TimeInterval extends AbstractModel
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
    protected $fillable = [
        'task_id',
        'start_at',
        'user_id',
        'end_at',
        'count_mouse',
        'count_keyboard',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'task_id' => 'integer',
        'user_id' => 'integer',
        'count_mouse' => 'integer',
        'count_keyboard' => 'integer',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'start_at',
        'end_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Override parent boot and Call deleting event
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function($vals) {
            /** @var TimeInterval $vals */
            foreach ($vals->screenshots()->get() as $screen) {
                $screen->delete();
            }
        });
    }

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

    /**
     * @return HasMany
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'entity_id')->where('entity_type', '=', Property::TIME_INTERVAL_CODE);
    }
}
