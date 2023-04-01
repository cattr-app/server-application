<?php

namespace App\Models;

use App\Contracts\ScreenshotService;
use App\Scopes\TimeIntervalAccessScope;
use Database\Factories\TimeIntervalFactory;
use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Storage;
use MatanYadaev\EloquentSpatial\SpatialBuilder as Builder;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

/**
 * App\Models\TimeInterval
 *
 * @property int $id
 * @property int $task_id
 * @property Carbon $start_at
 * @property Carbon $end_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property int $user_id
 * @property bool $is_manual
 * @property int|null $activity_fill
 * @property int|null $mouse_fill
 * @property int|null $keyboard_fill
 * @property array|null $location
 * @property-read Collection|TrackedApplication[] $apps
 * @property-read int|null $apps_count
 * @property-read bool $has_screenshot
 * @property-read Collection|Property[] $properties
 * @property-read int|null $properties_count
 * @property-read Task $task
 * @property-read User $user
 * @method static Builder|TimeInterval comparison($geometryColumn, $geometry, $relationship)
 * @method static Builder|TimeInterval contains($geometryColumn, $geometry)
 * @method static Builder|TimeInterval crosses($geometryColumn, $geometry)
 * @method static Builder|TimeInterval disjoint($geometryColumn, $geometry)
 * @method static Builder|TimeInterval distance($geometryColumn, $geometry, $distance)
 * @method static Builder|TimeInterval distanceExcludingSelf($geometryColumn, $geometry, $distance)
 * @method static Builder|TimeInterval distanceSphere($geometryColumn, $geometry, $distance)
 * @method static Builder|TimeInterval distanceSphereExcludingSelf($geometryColumn, $geometry, $distance)
 * @method static Builder|TimeInterval distanceSphereValue($geometryColumn, $geometry)
 * @method static Builder|TimeInterval distanceValue($geometryColumn, $geometry)
 * @method static Builder|TimeInterval doesTouch($geometryColumn, $geometry)
 * @method static Builder|TimeInterval equals($geometryColumn, $geometry)
 * @method static TimeIntervalFactory factory(...$parameters)
 * @method static Builder|TimeInterval intersects($geometryColumn, $geometry)
 * @method static Builder|TimeInterval newModelQuery()
 * @method static Builder|TimeInterval newQuery()
 * @method static QueryBuilder|TimeInterval onlyTrashed()
 * @method static Builder|TimeInterval orderByDistance($geometryColumn, $geometry, $direction = 'asc')
 * @method static Builder|TimeInterval orderByDistanceSphere($geometryColumn, $geometry, $direction = 'asc')
 * @method static Builder|TimeInterval orderBySpatial($geometryColumn, $geometry, $orderFunction, $direction = 'asc')
 * @method static Builder|TimeInterval overlaps($geometryColumn, $geometry)
 * @method static Builder|TimeInterval query()
 * @method static Builder|TimeInterval whereActivityFill($value)
 * @method static Builder|TimeInterval whereCreatedAt($value)
 * @method static Builder|TimeInterval whereDeletedAt($value)
 * @method static Builder|TimeInterval whereEndAt($value)
 * @method static Builder|TimeInterval whereId($value)
 * @method static Builder|TimeInterval whereIsManual($value)
 * @method static Builder|TimeInterval whereKeyboardFill($value)
 * @method static Builder|TimeInterval whereLocation($value)
 * @method static Builder|TimeInterval whereMouseFill($value)
 * @method static Builder|TimeInterval whereStartAt($value)
 * @method static Builder|TimeInterval whereTaskId($value)
 * @method static Builder|TimeInterval whereUpdatedAt($value)
 * @method static Builder|TimeInterval whereUserId($value)
 * @method static QueryBuilder|TimeInterval withTrashed()
 * @method static Builder|TimeInterval within($geometryColumn, $polygon)
 * @method static QueryBuilder|TimeInterval withoutTrashed()
 * @mixin EloquentIdeHelper
 */
class TimeInterval extends Model
{
    use SoftDeletes;
    use HasFactory;
    use HasSpatial;

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
        'location',
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
        'location' => Point::class,
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
     * Override parent boot and Call deleting event
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(new TimeIntervalAccessScope);

        static::deleting(static function ($interval) {
            /** @var TimeInterval $interval */
            $screenshotService = app()->make(ScreenshotService::class);
            $screenshotService->destroyScreenshot($interval);
        });
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id')->withoutGlobalScopes();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function properties(): MorphMany
    {
        return $this->morphMany(Property::class, 'entity');
    }

    public function hasScreenshot(): Attribute
    {
        return Attribute::make(
            get: static fn ($value) => !$value || Storage::exists(
                app(ScreenshotService::class)->getScreenshotPath($value['id'])
            )
        )->shouldCache();
    }

    public function location(): Attribute
    {
        return Attribute::make(
            get: static fn($value) => $value ? ['lat' => $value->latitude, 'lng' => $value->longitude] : null,
            set: static fn($value) => $value ? new Point($value['lat'], $value['lng']) : null,
        )->shouldCache();
    }

    public function apps(): HasMany
    {
        return $this->hasMany(TrackedApplication::class, 'time_interval_id');
    }
}
