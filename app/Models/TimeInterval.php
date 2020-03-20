<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @apiDefine TimeIntervalObject
 *
 * @apiSuccess {Integer}  timeInterval.id              ID
 * @apiSuccess {Integer}  timeInterval.task_id         The ID of the linked task
 * @apiSuccess {Integer}  timeInterval.user_id         The ID of the linked user
 * @apiSuccess {String}   timeInterval.start_at        DateTime of interval beginning
 * @apiSuccess {String}   timeInterval.end_at          DateTime of interval ending
 * @apiSuccess {Integer}  timeInterval.count_mouse     Count of mouse events
 * @apiSuccess {Integer}  timeInterval.count_keyboard  Count of keyboard events
 * @apiSuccess {ISO8601}  timeInterval.created_at      Creation DateTime
 * @apiSuccess {ISO8601}  timeInterval.updated_at      Update DateTime
 * @apiSuccess {ISO8601}  timeInterval.deleted_at      Delete DateTime or `NULL` if wasn't deleted
 * @apiSuccess {Array}    timeInterval.screenshots     Screenshots of this interval
 * @apiSuccess {Object}   timeInterval.user            The user that time interval belongs to
 * @apiSuccess {Object}   timeInterval.task            The task that time interval belongs to
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine TimeIntervalParams
 *
 * @apiParam {Integer}  [id]              ID
 * @apiParam {Integer}  [task_id]         The ID of the linked task
 * @apiParam {Integer}  [user_id]         The ID of the linked user
 * @apiParam {String}   [start_at]        DateTime of interval beginning
 * @apiParam {String}   [end_at]          DateTime of interval ending
 * @apiParam {Integer}  [count_mouse]     Count of mouse events
 * @apiParam {Integer}  [count_keyboard]  Count of keyboard events
 * @apiParam {ISO8601}  [created_at]      Creation DateTime
 * @apiParam {ISO8601}  [updated_at]      Update DateTime
 * @apiParam {ISO8601}  [deleted_at]      Delete DateTime
 *
 * @apiVersion 1.0.0
 */


/**
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
 * @mixin EloquentIdeHelper
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
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(static function ($intervals) {
            /** @var TimeInterval $intervals */
            $intervals->screenshot()->delete();
        });
    }

    public function screenshot(): HasOne
    {
        return $this->hasOne(Screenshot::class, 'time_interval_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'entity_id')->where('entity_type', '=', Property::TIME_INTERVAL_CODE);
    }
}
