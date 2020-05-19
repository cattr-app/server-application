<?php

namespace App\Models;

use App\Exceptions\Entities\AuthorizationException;
use Eloquent as EloquentIdeHelper;
use Exception;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Throwable;

/**
 * @apiDefine RoleObject
 *
 * @apiSuccess {Integer}  role.id          ID
 * @apiSuccess {Integer}  role.name        Name
 * @apiSuccess {ISO8601}  role.created_at  Creation DateTime
 * @apiSuccess {ISO8601}  role.updated_at  Update DateTime
 * @apiSuccess {ISO8601}  role.deleted_at  Delete DateTime or `NULL` if wasn't deleted
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine RoleParams
 *
 * @apiParam {Integer}  [id]          ID
 * @apiParam {Integer}  [name]        Name
 * @apiParam {ISO8601}  [created_at]  Creation DateTime
 * @apiParam {ISO8601}  [updated_at]  Update DateTime
 * @apiParam {ISO8601}  [deleted_at]  Delete DateTime
 * @apiParam {String}   [with]        For add relation model in response
 * @apiParam {Object}   [users]       Roles's relation users. All params in <a href="#api-User-GetUserList" >@User</a>
 * @apiParam {Object}   [rules]      Roles's relation rules. All params in<a href="#api-Rule-GetRulesActions">@Rules</a>
 *
 * @apiVersion 1.0.0
 */

/**
 * @property int $id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property User[] $users
 * @property Rule[] $rules
 * @property-read Collection|Project[] $projects
 * @method static bool|null forceDelete()
 * @method static QueryBuilder|Role onlyTrashed()
 * @method static bool|null restore()
 * @method static EloquentBuilder|Role whereCreatedAt($value)
 * @method static EloquentBuilder|Role whereDeletedAt($value)
 * @method static EloquentBuilder|Role whereId($value)
 * @method static EloquentBuilder|Role whereName($value)
 * @method static EloquentBuilder|Role whereUpdatedAt($value)
 * @method static QueryBuilder|Role withTrashed()
 * @method static QueryBuilder|Role withoutTrashed()
 * @mixin EloquentIdeHelper
 */
class Role extends Model
{
    use SoftDeletes;

    /**
     * table name from database
     *
     * @var string
     */
    protected $table = 'role';

    /**
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * @var array
     */
    protected $casts = [
        'name' => 'string'
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
     * @throws Exception
     */
    public static function updateRules(): void
    {
        /** @var array[] $actionList */
        $actionList = Rule::getActionList();

        /** @var Role $role */
        foreach (static::where([])->get() as $role) {
            foreach ($role->rules as $rule) {
                if (isset($actionList[$rule->object][$rule->action])) {
                    continue;
                }

                $rule->delete();
            }

            foreach ($actionList as $object => $actions) {
                foreach ($actions as $action => $name) {
                    $rule = Rule::withTrashed()->where([
                        'role_id' => $role->id,
                        'object' => $object,
                        'action' => $action,
                    ])->first();

                    if (!$rule) {
                        Rule::create([
                            'role_id' => $role->id,
                            'object' => $object,
                            'action' => $action,
                            'allow' => false,
                        ]);
                    } elseif ($rule->trashed()) {
                        $rule->restore();
                        $rule->allow = false;
                        $rule->save();
                    }
                }
            }
        }
    }

    /**
     * @param $role_id
     * @param $object
     * @param $action
     * @param $allow
     *
     * @return bool
     * @throws  Throwable
     */
    public static function updateAllow($role_id, $object, $action, $allow): bool
    {
        /** @var Rule $rule */
        $rule = Rule::query()->where([
            'role_id' => $role_id,
            'object' => $object,
            'action' => $action,
        ])->first();

        $user = Auth::user();

        if (!$rule) {
            return false;
        }

        if (!static::can($user, 'rules', 'full_access')) {
            throw_if(
                $user->role_id === $rule->role_id,
                new AuthorizationException(
                    AuthorizationException::ERROR_TYPE_FORBIDDEN,
                    'You cannot change your own privileges'
                )
            );
        }
        throw_if(
            $role_id === 1 && $object === 'rules' && $action === 'full_access',
            new AuthorizationException(
                AuthorizationException::ERROR_TYPE_FORBIDDEN,
                'You cannot change rule management for root'
            )
        );

        $rule->allow = $allow;
        return $rule->save();
    }

    /**
     * @param $user
     * @param $object
     * @param $action
     * @param $id
     *
     * @return bool
     */
    public static function can($user, $object, $action, $id = null): bool
    {
        if ((bool)$user->is_admin) {
            return true;
        }

        // TODO: need refactoring

        /** @var User $user */
        $userRoleIds = [$user->role_id];

        $projectID = request()->input('project_id', null);

        // Check access to the specific entity
        if (isset($id)) {
            $projectID = null;

            // Get ID of the related project
            switch ($object) {
                case 'projects':
                    $projectID = $id;
                    break;

                case 'tasks':
                    $task = Task::find($id);
                    if (isset($task)) {
                        $projectID = $task->project_id;
                    }
                    break;

                case 'time-intervals':
                    $interval = TimeInterval::with('task')->find($id);
                    if (isset($interval)) {
                        $projectID = $interval->task->project_id;
                    }
                    break;

                case 'screenshots':
                    $screenshot = Screenshot::with('timeInterval.task')->find($id);
                    if (isset($screenshot)) {
                        $projectID = $screenshot->timeInterval->task->project_id;
                    }
                    break;

                default:
                    break;
            }
        }

        if (isset($projectID)) {
            // Get role of the user in the project
            $projectUserRelation = ProjectsUsers::where([
                'project_id' => $projectID,
                'user_id' => $user->id,
            ])->first();
            if (isset($projectUserRelation)) {
                $userRoleIds[] = $projectUserRelation->role_id;
            }
        }

        $rules = Rule::query()->whereIn('role_id', $userRoleIds)->where([
            'object' => $object,
            'action' => $action,
        ])->get();

        foreach ($rules as $rule) {
            if ((bool)$rule->allow) {
                return true;
            }
        }

        return false;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id', 'id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class, 'role_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'projects_roles', 'role_id', 'project_id');
    }
}
