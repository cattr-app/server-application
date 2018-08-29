<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * @property string $deleted_at
 *
 * @property Role $role
 */
class Rule extends Model
{
    use SoftDeletes;


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
                'show' => __('Project show'),
                'edit' => __('Project edit'),
                'remove' => __('Project remove'),
                'relations' => __('Project list attached to user'),
                'full_access' => __('Project full access'),
            ],
            'projects-users' => [
                'list' => __('Project User relation list'),
                'create' => __('Project User relation create'),
                'bulk-create' => __('Project User relation multiple create'),
                'remove' => __('Project User relation remove'),
                'bulk-remove' => __('Project User relation multiple remove'),
                'full_access' => __('Project User relation full access'),
            ],
            'projects-roles' => [
                'list' => __('Project Role relation list'),
                'create' => __('Project Role relation create'),
                'bulk-create' => __('Project Role relation multiple create'),
                'remove' => __('Project Role relation remove'),
                'bulk-remove' => __('Project Role relation multiple remove'),
                'full_access' => __('Project Role relation full access'),
            ],
            'tasks' => [
                'list' => __('Task list'),
                'dashboard' => __('Task list at dashboard'),
                'create' => __('Task create'),
                'show' => __('Task show'),
                'edit' => __('Task edit'),
                'remove' => __('Task remove'),
                'full_access' => __('Tasks full access'),
            ],
            'roles' => [
                'list' => __('Role list'),
                'create' => __('Role create'),
                'edit' => __('Role edit'),
                'show' => __('Role show'),
                'remove' => __('Role remove'),
                'allowed-rules' => __('Role allowed rule list'),
                'full_access' => __('Roles full access'),
            ],
            'screenshots' => [
                'list' => __('Screenshot list'),
                'create' => __('Screenshot create'),
                'edit' => __('Screenshot edit'),
                'show' => __('Screenshot show'),
                'remove' => __('Screenshot remove'),
                'dashboard' => __('Screenshot list at dashboard'),
                'full_access' => __('Screenshots full access'),
            ],
            'time-intervals' => [
                'list' => __('Time interval list'),
                'create' => __('Time interval create'),
                'edit' => __('Time interval edit'),
                'show' => __('Time interval show'),
                'remove' => __('Time interval remove'),
                'full_access' => __('Time intervals full access'),
            ],
            'time' => [
                'total' => __('Time total'),
                'project' => __('Time by project'),
                'tasks' => __('Time by tasks'),
                'task' => __('Time by single task'),
                'task-user' => __('Time by single task and user'),
                'full_access' => __('Time full access'),
            ],
            'users' => [
                'list' => __('User list'),
                'create' => __('User create'),
                'edit' => __('User edit'),
                'show' => __('User show'),
                'remove' => __('User remove'),
                'bulk-edit' => __('User multiple edit'),
                'relations' => __('Attached users list'),
                'full_access' => __('Users full access'),
            ],
            'attached-users' => [
                'list' => __('Attached User relation list'),
                'create' => __('Attached User relation create'),
                'bulk-create' => __('Attached User relation multiple create'),
                'remove' => __('Attached User relation remove'),
                'bulk-remove' => __('Attached User relation multiple remove'),
                'full_access' => __('Attached User relation full access'),
            ],
            'rules' => [
                'edit' => __('Rules edit'),
                'bulk-edit' => __('Rules multiple edit'),
                'actions' => __('Rules actions list'),
            ],
            'project-report' => [
                'list' => __('Projects report list'),
                'projects' => __('Projects report related projects'),
            ],
        ]);
    }

}
