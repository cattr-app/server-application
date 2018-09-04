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
class Role extends Model
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
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'role_id');
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
                    }
                    elseif ($rule->trashed()) {
                        $rule->restore();
                        $rule->allow = false;
                        $rule->save();
                    }
                }
            }
        }
    }

    /**
     * @param $rule_id
     * @param $object
     * @param $action
     * @param $allow
     * @return bool
     */
    public static function updateAllow($rule_id, $object, $action, $allow): bool
    {
        $rule = Rule::where([
            'role_id' => $rule_id,
            'object' => $object,
            'action' => $action,
        ])->first();

        throw_if(!$rule, new \Exception('rule does not exist', 400));
        throw_if(Auth::user()->role_id === $rule->role_id, new \Exception('you cannot change your own privileges', 403));

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
        if (!isset($user->role)) {
            return false;
        }

        $rule = Rule::where([
            'role_id' => $user->role->id,
            'object' => $object,
            'action' => $action,
        ])->first();

        if (!$rule) {
            return false;
        }

        return (bool) $rule->allow;
    }

}
