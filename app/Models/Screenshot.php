<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @apiDefine ScreenshotObject
 *
 * @apiSuccess {Object}   screenshot                   Screenshot entity
 * @apiSuccess {Integer}  screenshot.id                ID
 * @apiSuccess {Integer}  screenshot.time_interval_id  Time interval ID
 * @apiSuccess {String}   screenshot.path              Image url
 * @apiSuccess {String}   screenshot.thumbnail_path    Thumbnail url
 * @apiSuccess {String}   screenshot.created_at        Creation DateTime
 * @apiSuccess {String}   screenshot.updated_at        Update DateTime
 * @apiSuccess {String}   screenshot.deleted_at        Delete DateTime or `NULL` if user wasn't deleted
 * @apiSuccess {Object}   screenshot.time_interval     The time interval that screenshot belongs to
 */


/**
 * Class Screenshot
 *
 * @package App\Models
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
 * @mixin Model
 */
class Screenshot extends AbstractModel
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

    /**
     * @return BelongsTo
     */
    public function timeInterval(): BelongsTo
    {
        return $this->belongsTo(TimeInterval::class, 'time_interval_id');
    }

    /**
     * @param null|User $user
     * @return bool
     */
    public function access($user): bool
    {
        if (!isset($user)) {
            return false;
        }

        $user_id = $this->timeInterval->user_id;

        // Allow root to see all screenshots.
        if (Role::can($user, 'screenshots', 'full_access')) {
            return true;
        }

        // Allow user to see own screenshots.
        if (Role::can($user, 'screenshots', 'list') && $user->id === $user_id) {
            return true;
        }

        // Allow manager to see screenshots of related users.
        if (Role::can($user, 'screenshots', 'manager_access')) {
            if (Role::can($user, 'projects', 'relations')) {
                $projectIds = $user->projects->pluck('id');
                $userIds = User::query()
                    ->whereHas('timeIntervals', function (EloquentBuilder $query) use ($projectIds) {
                        $query->whereHas('task', function (EloquentBuilder $query) use ($projectIds) {
                            $query->whereHas('project', function (EloquentBuilder $query) use ($projectIds) {
                                $query->whereIn('id', $projectIds);
                            });
                        });
                    })->select('id')->get('id')->pluck('id');

                if ($userIds->contains($user_id)) {
                    return true;
                }
            }
        }
        User::whereHas('timeIntervals', function (EloquentBuilder $query) {
            echo get_class($query);
        });

        return false;
    }

}
