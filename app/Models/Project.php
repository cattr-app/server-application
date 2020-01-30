<?php

namespace App\Models;

use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @apiDefine ProjectObject
 *
 * @apiSuccess {Integer}  project.id            ID
 * @apiSuccess {Integer}  [project.company_id]  Company ID
 * @apiSuccess {String}   project.name          Name
 * @apiSuccess {String}   project.description   Description of project
 * @apiSuccess {ISO8601}  project.created_at    Creation DateTime
 * @apiSuccess {ISO8601}  project.updated_at    Update DateTime
 * @apiSuccess {ISO8601}  project.deleted_at    Delete DateTime or `NULL` if wasn't deleted
 * @apiSuccess {Array}    [project.users]       Users attached to project
 * @apiSuccess {Array}    [project.tasks]       Tasks of project
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine ProjectParams
 *
 * @apiParam {Integer}  [id]           ID
 * @apiParam {Integer}  [company_id]   Company ID
 * @apiParam {String}   [name]         Name
 * @apiParam {String}   [description]  Description of project
 * @apiParam {ISO8601}  [created_at]   Creation DateTime
 * @apiParam {ISO8601}  [updated_at]   Update DateTime
 * @apiParam {ISO8601}  [deleted_at]   Delete DateTime or `NULL` if user wasn't deleted
 * @apiParam {String}   [with]         Related models to return in response
 * @apiParam {Object}   [users]        Users attached to project, all params in <a href="#api-User-GetUserList" >@User</a>
 * @apiParam {Object}   [tasks]        Tasks of project, all params in <a href="#api-Task-GetTaskList" >@Task</a>
 *
 * @apiVersion 1.0.0
 */

/**
 * Class Project
 *
 * @package App\Models
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $description
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property bool $important
 * @property User $users
 * @property Task[] $tasks
 * @property-read Collection|Role[] $roles
 * @property-read Collection|ProjectsUsers[] $usersRelation
 * @method static bool|null forceDelete()
 * @method static QueryBuilder|Project onlyTrashed()
 * @method static bool|null restore()
 * @method static EloquentBuilder|Project whereCompanyId($value)
 * @method static EloquentBuilder|Project whereCreatedAt($value)
 * @method static EloquentBuilder|Project whereDeletedAt($value)
 * @method static EloquentBuilder|Project whereDescription($value)
 * @method static EloquentBuilder|Project whereId($value)
 * @method static EloquentBuilder|Project whereImportant($value)
 * @method static EloquentBuilder|Project whereName($value)
 * @method static EloquentBuilder|Project whereUpdatedAt($value)
 * @method static QueryBuilder|Project withTrashed()
 * @method static QueryBuilder|Project withoutTrashed()
 * @mixin EloquentIdeHelper
 */
class Project extends AbstractModel
{
    use SoftDeletes;

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

        static::deleting(function($project) {
            /** @noinspection PhpExpressionResultUnusedInspection */
            $project->tasks()->delete();
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
     * @return HasMany
     */
    public function usersRelation(): HasMany
    {
        return $this->hasMany(ProjectsUsers::class, 'project_id', 'id');
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
     * @return array
     */
    public static function getUserRelatedProjectIds($user): array
    {
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

        $user_time_interval_project_id = collect($user->timeIntervals)->flatMap(function ($interval) {
            if (isset($interval->task->project)) {
                return collect($interval->task->project->id);
            }

            return null;
        });

        $project_ids = collect([$project_ids, $user_tasks_project_id, $user_time_interval_project_id])->collapse();

        return $project_ids->toArray();
    }
}
