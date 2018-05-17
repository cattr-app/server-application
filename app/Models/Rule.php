<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Rule
 * @package App\Models
 *
 * @property int $id
 * @property int $role_id
 * @property string $object
 * @property string $action
 * @property bool $allow
 * @property string $created_at
 * @property string $updated_at
 *
 * @property Role $role
 */
class Rule extends Model
{

    /**
     * table name from database
     * @var string
     */
    protected $table = 'rule';

    /**
     * @var array
     */
    protected $fillable = ['role_id', 'object', 'action', 'allow'];

    /**
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * @return array[]
     */
    public static function getActionList(): array
    {
        return \Filter::process('role.actions.list', [
            'projects' => [
                'list' => __('Project list'),
                'create' => __('Project create'),
                'view' => __('Project view'),
                'show' => __('Project show'),
                'edit' => __('Project edit'),
                'remove' => __('Project remove'),
            ],
            'tasks' => [
                'list' => __('Task list'),
                'dashboard' => __('Task list at dashboard'),
                'create' => __('Task create'),
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
                'get' => __('Screenshot get by interval id'),
                'remove' => __('Screenshot remove'),
                'dashboard' => __('Screenshot list at dashboard'),
            ],
            'timeintervals' => [
                'list' => __('Time interval list'),
                'create' => __('Time interval create'),
                'edit' => __('Time interval edit'),
                'show' => __('Time interval show'),
                'remove' => __('Time interval remove'),
            ],
            'time' => [
                'total' => __('Time total'),
                'project' => __('Time by project'),
                'tasks' => __('Time by tasks'),
                'task' => __('Time by single task'),
                'task-user' => __('Time by single task and user'),
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
