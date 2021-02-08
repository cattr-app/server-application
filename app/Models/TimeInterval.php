<?php

namespace App\Models;

use App\Contracts\ScreenshotService;
use App\Scopes\TimeIntervalScope;
use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Storage;

/**
 * @apiDefine TimeIntervalObject
 *
 * @apiSuccess {Integer}  timeInterval.id              ID
 * @apiSuccess {Integer}  timeInterval.task_id         The ID of the linked task
 * @apiSuccess {Integer}  timeInterval.user_id         The ID of the linked user
 * @apiSuccess {String}   timeInterval.start_at        DateTime of interval beginning
 * @apiSuccess {String}   timeInterval.end_at          DateTime of interval ending
 * @apiSuccess {Integer}  timeInterval.activity_fill   Activity rate as a percentage
 * @apiSuccess {Integer}  timeInterval.mouse_fill      Time spent using the mouse as a percentage
 * @apiSuccess {Integer}  timeInterval.keyboard_fill   Time spent using the keyboard as a percentage
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
 * @apiParam {Integer}  [id]             ID
 * @apiParam {Integer}  [task_id]        The ID of the linked task
 * @apiParam {Integer}  [user_id]        The ID of the linked user
 * @apiParam {String}   [start_at]       DateTime of interval beginning
 * @apiParam {String}   [end_at]         DateTime of interval ending
 * @apiParam {Integer}  [activity_fill]  Activity rate as a percentage
 * @apiParam {Integer}  [mouse_fill]     Time spent using the mouse as a percentage
 * @apiParam {Integer}  [keyboard_fill]  Time spent using the keyboard as a percentage
 * @apiParam {ISO8601}  [created_at]     Creation DateTime
 * @apiParam {ISO8601}  [updated_at]     Update DateTime
 * @apiParam {ISO8601}  [deleted_at]     Delete DateTime
 *
 * @apiVersion 1.0.0
 */


/**
 * App\Models\TimeInterval
 *
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property int $activity_fill
 * @property int $mouse_fill
 * @property int $keyboard_fill
 * @property string $start_at
 * @property string $end_at
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property bool $is_manual
 * @property Task $task
 * @property User $user
 * @property-read int|null $properties_count
 * @property-read Collection|Property[] $properties
 * @method static bool|null forceDelete()
 * @method static bool|null restore()
 * @method static EloquentBuilder|TimeInterval whereActivityFill($value)
 * @method static EloquentBuilder|TimeInterval whereMouseFill($value)
 * @method static EloquentBuilder|TimeInterval whereKeyboardFill($value)
 * @method static EloquentBuilder|TimeInterval whereCreatedAt($value)
 * @method static EloquentBuilder|TimeInterval whereDeletedAt($value)
 * @method static EloquentBuilder|TimeInterval whereEndAt($value)
 * @method static EloquentBuilder|TimeInterval whereId($value)
 * @method static EloquentBuilder|TimeInterval whereStartAt($value)
 * @method static EloquentBuilder|TimeInterval whereTaskId($value)
 * @method static EloquentBuilder|TimeInterval whereUpdatedAt($value)
 * @method static EloquentBuilder|TimeInterval whereUserId($value)
 * @method static EloquentBuilder|TimeInterval whereIsManual($value)
 * @method static EloquentBuilder|TimeInterval newModelQuery()
 * @method static EloquentBuilder|TimeInterval newQuery()
 * @method static EloquentBuilder|TimeInterval query()
 * @method static QueryBuilder|TimeInterval withTrashed()
 * @method static QueryBuilder|TimeInterval withoutTrashed()
 * @method static QueryBuilder|TimeInterval onlyTrashed()
 * @mixin EloquentIdeHelper
 */
class TimeInterval extends Model
{
    use SoftDeletes;
    use HasFactory;

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
        'activity_fill',
        'mouse_fill',
        'keyboard_fill',
        'is_manual',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'task_id' => 'integer',
        'user_id' => 'integer',
        'activity_fill' => 'integer',
        'mouse_fill' => 'integer',
        'keyboard_fill' => 'integer',
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

    protected $appends = ['has_screenshot'];
    /**
     * @var ScreenshotService
     */
    private ScreenshotService $screenshotService;

    /**
     * Override parent boot and Call deleting event
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(new TimeIntervalScope);

        static::deleting(function ($interval) {
            /** @var TimeInterval $interval */

            $this->screenshotService->destroyScreenshot($interval);
        });
    }

    public function __construct(array $attributes = [])
    {
        $this->screenshotService = app()->make(ScreenshotService::class);

        parent::__construct($attributes);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id')->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'entity_id')->where('entity_type', '=', Property::TIME_INTERVAL_CODE);
    }

    public function getHasScreenshotAttribute(): bool
    {
        return Storage::exists($this->screenshotService->getScreenshotPath($this));
    }
}
