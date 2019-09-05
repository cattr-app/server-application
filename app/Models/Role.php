<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\User;
use Filter;
use Auth;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Role
 * @package App\Models
 *
 * @property int $id
 * @property string $name
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 *
 * @property User[] $users
 * @property Rule[] $rules
 */
class Role extends AbstractModel
{
    use SoftDeletes;

    /**
     * table name from database
     * @var string
     */
    protected $table = 'role';

    /**
     * @var array
     */
    protected $fillable = ['name'];

    /**
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_role', 'role_id', 'user_id', 'id', 'id');
    }

    /**
     * Attach this role to user
     * @param int|User $user
     */
    public function attachToUser($user)
    {
        $userId = $user;
        if ($user instanceof User) {
            $userId = $user->id;
        }
        $this->users()->attach($userId);
    }

    /**
     * Detach this role from user
     * @param int|User $user
     */
    public function detachFromUser($user)
    {
        $userId = $user;
        if ($user instanceof User) {
            $userId = $user->id;
        }
        $this->users()->detach($userId);
    }

    /**
     * @return HasMany
     */
    public function rules(): HasMany
    {
        return $this->hasMany(Rule::class, 'role_id');
    }

    /**
     * @return BelongsToMany
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'projects_roles', 'role_id', 'project_id');
    }

    /**
     * @throws \Exception
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
     * @return bool
     * @throws \Throwable
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

        throw_if(!$rule, new \Exception('rule does not exist', 400));
        if (!static::can($user, 'rules', 'full_access')) {
            $userRoleIds = $user->rolesIds();
            throw_if($userRoleIds->contains($rule->role_id), new \Exception('you cannot change your own privileges', 403));
        }
        throw_if($role_id === 1 && $object === 'rules' && $action === 'full_access', new \Exception('you cannot change rule management for root', 403));

        $rule->allow = $allow;
        return $rule->save();
    }

    /**
     * @param $user
     * @param $object
     * @param $action
     * @return bool
     */
    public static function can($user, $object, $action): bool
    {
        /** @var User $user */
        $userRoleIds = $user->rolesIds();

        $rule = Rule::query()->whereIn('role_id', $userRoleIds)->where([
            'object' => $object,
            'action' => $action,
        ])->first();

        if (!$rule) {
            return false;
        }

        return (bool)$rule->allow;
    }

}
