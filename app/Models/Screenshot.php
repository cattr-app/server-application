<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Screenshot
 * @package App\Models
 *
 * @property int $id
 * @property int $time_interval_id
 * @property string $path
 * @property string $thumbnail_path
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property bool $important
 *
 * @property TimeInterval $timeInterval
 */
class Screenshot extends Model
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
     * @return BelongsTo
     */
    public function timeInterval(): BelongsTo
    {
        return $this->belongsTo(TimeInterval::class, 'time_interval_id');
    }

    /**
     * @param null|User $user
     */
    public function access($user) : bool
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
            if (Role::can($user, 'users', 'relations')) {
                $attached_user_ids = $user->attached_users->pluck('id');
                if ($attached_user_ids->contains($user_id)) {
                    return true;
                }
            }

            if (Role::can($user, 'projects', 'relations')) {
                $attached_project_ids = $user->projects->pluck('id');
                $related_user_ids = User::whereHas('timeIntervals', function ($query) use ($attached_project_ids) {
                    $query->whereHas('task', function ($query) use ($attached_project_ids) {
                        $query->whereHas('project', function ($query) use ($attached_project_ids) {
                            $query->whereIn('id', $attached_project_ids);
                        });
                    });
                })->select('id')->get('id')->pluck('id');
                if ($related_user_ids->contains($user_id)) {
                    return true;
                }
            }
        }

        return false;
    }
}
