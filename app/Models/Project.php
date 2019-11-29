<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Project
 * @package App\Models
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property bool $important
 *
 * @property User $users
 * @property Task[] $tasks
 */
class Project extends AbstractModel
{
    use SoftDeletes;

    /**
     * table name from database
     * @var string
     */
    protected $table = 'projects';

    /**
     * @var array
     */
    protected $fillable = [
        'company_id',
        'name',
        'description',
        'important',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'company_id' => 'integer',
        'name' => 'string',
        'description' => 'string',
        'important' => 'integer',
    ];

    /**
     * @var array
     */
    protected $dates = [
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Override parent boot and Call deleting event
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function($projects) {
            /** @var Project $projects */
            foreach ($projects->tasks()->get() as $task) {
                $task->delete();
            }
        });
    }

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'projects_users', 'project_id', 'user_id');
    }

    /**
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'projects_roles', 'project_id', 'role_id');
    }

    /**
     * @return HasMany
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    /**
     * @param User $user
     *
     * @return int[]
     */
    public static function getUserRelatedProjectIds($user): array {
        $full_access = Role::can($user, 'projects', 'full_access');

        if ($full_access) {
            return static::all(['id'])->pluck('id')->toArray();
        }

        $user_project_ids = collect($user->projects)->pluck('id');
        $project_ids = collect($user_project_ids);

        if (count($project_ids) <= 0) {
            return static::all(['id'])->pluck('id')->toArray();
        }

        $user_tasks_project_id = collect($user->tasks)->flatMap(function ($task) {
            if (isset($task->project)) {
                return collect($task->project->id);
            }

            return null;
        });

        $user_time_interval_project_id = collect($user->timeIntervals)->flatMap(function ($val) {
            if (isset($val->task->project)) {
                return collect($val->task->project->id);
            }

            return null;
        });

        $project_ids = collect([$project_ids, $user_tasks_project_id, $user_time_interval_project_id])->collapse();

        return $project_ids->toArray();
    }
}
