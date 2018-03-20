<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use App\User;


class Rule extends Model
{

    /**
     * table name from database
     * @var string
     */
    protected $table = 'rule';

    protected $fillable = array('role_id','object','action','allow');



    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }


    public static function getActionList()
    {
        return \Filter::process('role.actions.list', [
            'projects' => [
                'list' => __('Project list'),
                'view' => __('Project view'),
                'edit' => __('Project edit'),
                'remove' => __('Project remove'),
            ],
            'tasks' => [
                'list' => __('Task list'),
                'view' => __('Task view'),
                'edit' => __('Task edit'),
                'remove' => __('Task remove'),
            ],
            'roles' => [
                'list' => __('Role list'),
                'create' => __('Role create'),
                'edit' => __('Role edit'),
                'show' => __('Role show'),
                'remove' => __('Role remove'),
            ],
            'users' => [
                'list' => __('User list'),
                'create' => __('User create'),
                'edit' => __('User edit'),
                'show' => __('User show'),
                'remove' => __('User remove'),
            ],
            'actions' => [
                'list' => __('Actions list'),
            ],
            'rules' => [
                'edit' => __('Rules edit'),
            ],
        ]);
    }

}