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


    public static function getActionList(): array
    {
        return \Filter::process('role.actions.list', [
            'projects' => [
                'list' => __('Project list'),
                'create' => __('Project create'),
                'view' => __('Project view'),
                'edit' => __('Project edit'),
                'remove' => __('Project remove'),
            ],
            'tasks' => [
                'list' => __('Task list'),
                'create' => __('Project create'),
                'view' => __('Task view'),
                'show' => __('Task show'),
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
            'screenshots' => [
                'list' => __('Screenshot list'),
                'create' => __('Screenshot create'),
                'edit' => __('Screenshot edit'),
                'show' => __('Screenshot show'),
                'remove' => __('Screenshot remove'),
            ],
            'timeintervals' => [
                'list' => __('Time interval list'),
                'create' => __('Time interval create'),
                'edit' => __('Time interval edit'),
                'show' => __('Time interval show'),
                'remove' => __('Time interval remove'),
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
            'allowed' => [
                'list' => __('Allowed actions list'),
            ],
            'rules' => [
                'edit' => __('Rules edit'),
            ],
        ]);
    }

}
