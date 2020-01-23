<?php

namespace App\Models;

use App\User;
use Eloquent;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @apiDefine ScreenshotObject
 *
 * @apiSuccess {Object}   timeInterval                 Time interval entity
 * @apiSuccess {Integer}  timeInterval.id              ID
 * @apiSuccess {Integer}  timeInterval.task_id         The ID of the linked task
 * @apiSuccess {Integer}  timeInterval.user_id         The ID of the linked user
 * @apiSuccess {String}   timeInterval.start_at        DateTime of interval beginning
 * @apiSuccess {String}   timeInterval.end_at          DateTime of interval ending
 * @apiSuccess {Integer}  timeInterval.count_mouse     Count of mouse events
 * @apiSuccess {Integer}  timeInterval.count_keyboard  Count of keyboard events
 * @apiSuccess {String}   timeInterval.created_at      Creation DateTime
 * @apiSuccess {String}   timeInterval.updated_at      Update DateTime
 * @apiSuccess {String}   timeInterval.deleted_at      Delete DateTime or `NULL` if user wasn't deleted
 * @apiSuccess {Array}    timeInterval.screenshots     Screenshots of this interval
 * @apiSuccess {Object}   timeInterval.user            The user that time interval belongs to
 * @apiSuccess {Object}   timeInterval.task            The task that time interval belongs to
 */


/**
 * Class TimeInterval
 *
 * @package App\Models
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property string $start_at
 * @property string $end_at
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property Task $task
 * @property User $user
 * @property Screenshot[] $screenshots
 * @property int $count_mouse
 * @property int $count_keyboard
 * @property-read Collection|Property[] $properties
 * @property-read Screenshot $screenshot
 * @method static bool|null forceDelete()
 * @method static QueryBuilder|TimeInterval onlyTrashed()
 * @method static bool|null restore()
 * @method static EloquentBuilder|TimeInterval whereCountKeyboard($value)
 * @method static EloquentBuilder|TimeInterval whereCountMouse($value)
 * @method static EloquentBuilder|TimeInterval whereCreatedAt($value)
 * @method static EloquentBuilder|TimeInterval whereDeletedAt($value)
 * @method static EloquentBuilder|TimeInterval whereEndAt($value)
 * @method static EloquentBuilder|TimeInterval whereId($value)
 * @method static EloquentBuilder|TimeInterval whereStartAt($value)
 * @method static EloquentBuilder|TimeInterval whereTaskId($value)
 * @method static EloquentBuilder|TimeInterval whereUpdatedAt($value)
 * @method static EloquentBuilder|TimeInterval whereUserId($value)
 * @method static QueryBuilder|TimeInterval withTrashed()
 * @method static QueryBuilder|TimeInterval withoutTrashed()
 * @method static Model|TimeInterval make($attributes)
 * @mixin Eloquent
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
        'is_manual',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'task_id' => 'integer',
        'user_id' => 'integer',
        'count_mouse' => 'integer',
        'count_keyboard' => 'integer',
        'is_manual' => 'boolean',
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

        static::deleting(function($intervals) {
            /** @var TimeInterval $intervals */
            $intervals->screenshot()->delete();
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
     * @return HasOne
     */
    public function screenshot(): HasOne
    {
    	return $this->hasOne(Screenshot::class, 'time_interval_id');
    }

    /**
     * @return HasMany
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'entity_id')->where('entity_type', '=', Property::TIME_INTERVAL_CODE);
    }
}
