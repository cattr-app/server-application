<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image;
use Storage;

/**
 * @apiDefine ScreenshotObject
 *
 * @apiSuccess {Integer}  screenshot.id                ID
 * @apiSuccess {Integer}  screenshot.time_interval_id  Time interval ID
 * @apiSuccess {String}   screenshot.path              Image url
 * @apiSuccess {String}   screenshot.thumbnail_path    Thumbnail url
 * @apiSuccess {ISO8601}  screenshot.created_at        Creation DateTime
 * @apiSuccess {ISO8601}  screenshot.updated_at        Update DateTime
 * @apiSuccess {ISO8601}  screenshot.deleted_at        Delete DateTime or `NULL` if wasn't deleted
 * @apiSuccess {Object}   screenshot.time_interval     The time interval that screenshot belongs to
 * @apiSuccess {Boolean}  screenshot.important         Important flag
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine ScreenshotParams
 *
 * @apiParam {Integer}  [id]                ID
 * @apiParam {Integer}  [time_interval_id]  Time interval ID
 * @apiParam {String}   [path]              Image url
 * @apiParam {String}   [thumbnail_path]    Thumbnail url
 * @apiParam {ISO8601}  [created_at]        Creation DateTime
 * @apiParam {ISO8601}  [updated_at]        Update DateTime
 * @apiParam {ISO8601}  [deleted_at]        Delete DateTime
 * @apiParam {Object}   [time_interval]     The time interval that screenshot belongs to
 * @apiParam {Boolean}  [important]         Important flag
 *
 * @apiVersion 1.0.0
 */

/**
 * @property int $id
 * @property int $time_interval_id
 * @property string $path
 * @property string $thumbnail_path
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property bool $important
 * @property TimeInterval $timeInterval
 * @property bool $is_removed
 * @method static bool|null forceDelete()
 * @method static QueryBuilder|Screenshot onlyTrashed()
 * @method static bool|null restore()
 * @method static EloquentBuilder|Screenshot whereCreatedAt($value)
 * @method static EloquentBuilder|Screenshot whereDeletedAt($value)
 * @method static EloquentBuilder|Screenshot whereId($value)
 * @method static EloquentBuilder|Screenshot whereImportant($value)
 * @method static EloquentBuilder|Screenshot whereIsRemoved($value)
 * @method static EloquentBuilder|Screenshot wherePath($value)
 * @method static EloquentBuilder|Screenshot whereThumbnailPath($value)
 * @method static EloquentBuilder|Screenshot whereTimeIntervalId($value)
 * @method static EloquentBuilder|Screenshot whereUpdatedAt($value)
 * @method static QueryBuilder|Screenshot withTrashed()
 * @method static QueryBuilder|Screenshot withoutTrashed()
 * @mixin EloquentIdeHelper
 */
class Screenshot extends Model
{
    use SoftDeletes;

    public const DEFAULT_PATH = 'public/none.png';
    public const CAP_PATH = 'storage/none.png';

    /**
     * table name from database
     * @var string
     */
    protected $table = 'screenshots';

    /**
     * @var array
     */
    protected $fillable = [
        'time_interval_id',
        'path',
        'thumbnail_path',
        'important',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'time_interval_id' => 'integer',
        'path' => 'string',
        'thumbnail_path' => 'string',
        'important' => 'boolean',
        'is_removed' => 'boolean',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public static function createByInterval(TimeInterval $timeInterval, string $path = ''): Screenshot
    {
        $screenshot = Image::make(storage_path('app/' . ($path ?: self::DEFAULT_PATH)));
        $thumbnail = $screenshot->resize(280, null, fn(Constraint $constraint) => $constraint->aspectRatio());
        $thumbnailPath = str_replace(
            'uploads/screenshots',
            'uploads/screenshots/thumbs',
            $path ?: self::DEFAULT_PATH
        );

        Storage::put($thumbnailPath, (string)$thumbnail->encode());

        $screenshotData = [
            'time_interval_id' => $timeInterval->id,
            'path' => $path ?: self::CAP_PATH,
            'thumbnail_path' => $thumbnailPath,
        ];

        return self::create($screenshotData);
    }

    public function timeInterval(): BelongsTo
    {
        return $this->belongsTo(TimeInterval::class, 'time_interval_id');
    }

    public function access(?User $user): bool
    {
        if (!isset($user)) {
            return false;
        }

        $userId = $this->timeInterval->user_id;

        // Allow root to see all screenshots.
        if (Role::can($user, 'screenshots', 'full_access')) {
            return true;
        }

        // Allow users with rule see all screenshots.
        if (Role::can($user, 'screenshots', 'list')) {
            return true;
        }

        // Allow user to see own screenshots.
        if ($user->id === $userId) {
            return true;
        }

        // Allow a manager to see screenshots of related users.
        if (Role::can($user, 'screenshots', 'manager_access') && Role::can($user, 'projects', 'relations')) {
            $projectIds = $user->projects->pluck('id');
            $userIds = User::query()
                ->whereHas('timeIntervals', static function (EloquentBuilder $query) use ($projectIds) {
                    $query->whereHas('task', static function (EloquentBuilder $query) use ($projectIds) {
                        $query->whereHas('project', static function (EloquentBuilder $query) use ($projectIds) {
                            $query->whereIn('id', $projectIds);
                        });
                    });
                })->select('id')->get('id')->pluck('id');

            if ($userIds->contains($userId)) {
                return true;
            }
        }
        return false;
    }
}
