<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\User;
use Illuminate\Database\Eloquent\SoftDeletes;


class Role extends Model
{
    use SoftDeletes;


    /**
     * table name from database
     * @var string
     */
    protected $table = 'role';

    protected $fillable = array('name');


    public function users()
    {
        return $this->hasMany(User::class, 'role_id');
    }


    public static function updateRules()
    {
        $actionList = Rule::getActionList();


        foreach (static::where([])->get() as $role) {

            foreach ($role->rules as $rule) {

                if (
                    isset($actionList[$rule->object]) &&
                    isset($actionList[$rule->object][$rule->action])) {
                    continue;
                }

                $rule->delete();
            }


            foreach ($actionList as $object => $actions) {

                foreach ($actions as $action => $name) {

                    $rule = Rule::where([
                        'role_id' => $role->id,
                        'object' => $object,
                        'action' => $action,
                    ])->first();


                    if(!$rule) {

                        $rule = new Rule;

                        $rule->role_id = $role->id;
                        $rule->object = $object;
                        $rule->action = $action;
                        $rule->allow = false;

                        $rule->save();
                    }


                }
            }
        }
    }

    public function rules()
    {
        return $this->hasMany(Rule::class, 'role_id');
    }



    public static function can($user, $object, $action)
    {

        $rule = Rule::where([
            'role_id' => $user->role->id,
            'object' => $object,
            'action' => $action,
        ])->first();



        if(!$rule)
            return false;

        return $rule->allow;
    }

}