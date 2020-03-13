<?php

namespace App\Models;

use App\EventFilter\Facades\Filter;
use Eloquent as EloquentIdeHelper;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @apiDefine register_create Register user
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_list Project list
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_create Project create
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_show Project show
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_edit Project edit
 * As default, this permission have:
 * - root
 * - manager
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_remove Project remove
 * As default, this permission have:
 * - root
 * - manager
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_relations Project list attached to user
 * As default, this permission have:
 * - root
 * - client
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_full_access Project full access
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_count Project count
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_tasks Project tasks
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_users_list Project User relation list
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_users_create Project User relation create
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_users_bulk_create Project User relation multiple create
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_users_remove Project User relation remove
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_users_bulk_remove Project User relation multiple remove
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_users_full_access Project User relation full access
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_roles_list Project Role relation list
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_roles_create Project Role relation create
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_roles_bulk_create Project Role relation multiple create
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_roles_remove Project Role relation remove
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_roles_bulk_remove Project Role relation multiple remove
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine projects_roles_full_access Project Role relation full access
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine tasks_list Task list
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine tasks_dashboard Task list at dashboard
 * As default, this permission have:
 * - root
 * - observer
 * - client
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine tasks_create Task create
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine tasks_show Task show
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine tasks_edit Task edit
 * As default, this permission have:
 * - root
 * - manager
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine tasks_remove Task remove
 * As default, this permission have:
 * - root
 * - manager
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine tasks_full_access Task full access
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine tasks_count Task count
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine task_comment_list Task comments list
 * As default, this permission have:
 * - root
 * - user
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine task_comment_create Task comment create
 * As default, this permission have:
 * - root
 * - user
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine task_comment_show Task comment show
 * As default, this permission have:
 * - root
 * - user
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine task_comment_remove Task comment remove
 * As default, this permission have:
 * - root
 * - user
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine task_comment_full_access Task comment full access
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine roles_list Role list
 * As default, this permission have:
 * - root
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine roles_create Role create
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine roles_edit Role edit
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine roles_show Role show
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine roles_remove Role remove
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine roles_allowed_rules Role allowed rule list
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine roles_full_access Roles full access
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine roles_count Role count
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine screenshots_list Screenshot list
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine screenshots_create Screenshot create
 * As default, this permission have:
 * - root
 * - manager
 * - auditor
 * - user
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine screenshots_edit Screenshot edit
 * As default, this permission have:
 * - root
 * - manager
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine screenshots_show Screenshot show
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine screenshots_remove Screenshot remove
 * As default, this permission have:
 * - root
 * - manager
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine screenshots_remove_related Remove screenshots of related users
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine screenshots_dashboard Screenshot list at dashboard
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine screenshots_manager_access Screenshots manager access
 * As default, this permission have:
 * - root
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine screenshots_full_access Screenshots full access
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_intervals_list Time interval list
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_intervals_create Time interval create
 * As default, this permission have:
 * - root
 * - manager
 * - auditor
 * - user
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_intervals_edit Time interval edit
 * As default, this permission have:
 * - root
 * - manager
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_intervals_show Time interval show
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_intervals_remove Time interval remove
 * As default, this permission have:
 * - root
 * - manager
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_intervals_bulk_remove Time interval bulk remove
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_intervals_full_access Time intervals full access
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_intervals_manager_access Time intervals manager access
 * As default, this permission have:
 * - root
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_total Time total
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 * - user
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_project Time by project
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 * - user
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_tasks Time by tasks
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 * - user
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_task Time by single task
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 * - user
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_task_user Time by single task and user
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 * - user
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_full_access Time full access
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine users_list User list
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine users_create User create
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine users_edit User edit
 * As default, this permission have:
 * - root
 * - manager
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine users_show User show
 * As default, this permission have:
 * - root
 * - observer
 * - client
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine users_remove User remove
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine users_bulk_edit User multiple edit
 * As default, this permission have:
 * - root
 * - manager
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine users_relations Attached users list
 * As default, this permission have:
 * - root
 * - observer
 * - client
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine users_full_access Users full access
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine users_manager_access Users manager access
 * As default, this permission have:
 * - root
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine users_count User count
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine attached_users_list Attached User relation list
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine attached_users_create Attached User relation create
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine attached_users_bulk_create Attached User relation multiple create
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine attached_users_remove Attached User relation remove
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine attached_users_bulk_remove Attached User relation multiple remove
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine attached_users_full_access Attached User relation full access
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine rules_edit Rules edit
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine rules_bulk_edit Rules multiple edit
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine rules_actions Rules actions list
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine rules_full_access Rules full access
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine project_report_list Project report list
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine project_report_projects Project report related projects
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine project_report_manager_access Project report list
 * As default, this permission have:
 * - root
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_duration_list Time duration list
 * As default, this permission have:
 * - root
 * - manager
 * - auditor
 * - user
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine time_use_report_list Time use report list
 * As default, this permission have:
 * - root
 * - manager
 * - auditor
 * - user
 *
 * @apiVersion 1.0.0
 */

/**
 * @apiDefine dashboard_manager_access Dashboard manager access
 * As default, this permission have:
 * - root
 * - manager
 * - auditor
 *
 * @apiVersion 1.0.0
 */

/**
 * Class Rule
 *
 * @property int $id
 * @property int $role_id
 * @property string $object
 * @property string $action
 * @property bool $allow
 * @property string $created_at
 * @property string $updated_at
 * @property string $deleted_at
 * @property Role $role
 * @method static bool|null forceDelete()
 * @method static QueryBuilder|Rule onlyTrashed()
 * @method static bool|null restore()
 * @method static EloquentBuilder|Rule whereAction($value)
 * @method static EloquentBuilder|Rule whereAllow($value)
 * @method static EloquentBuilder|Rule whereCreatedAt($value)
 * @method static EloquentBuilder|Rule whereDeletedAt($value)
 * @method static EloquentBuilder|Rule whereId($value)
 * @method static EloquentBuilder|Rule whereObject($value)
 * @method static EloquentBuilder|Rule whereRoleId($value)
 * @method static EloquentBuilder|Rule whereUpdatedAt($value)
 * @method static QueryBuilder|Rule withTrashed()
 * @method static QueryBuilder|Rule withoutTrashed()
 * @mixin EloquentIdeHelper
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
    protected $fillable = [
        'role_id',
        'object',
        'action',
        'allow',
    ];

    /**
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * @return array
     */
    public static function getActionList(): array
    {
        return Filter::process('role.actions.list', [
            'register' => [
                'create' => __('Register user'),
            ],
            'projects' => [
                'list' => __('Project list'),
                'create' => __('Project create'),
                'show' => __('Project show'),
                'edit' => __('Project edit'),
                'remove' => __('Project remove'),
                'relations' => __('Project list attached to user'),
                'full_access' => __('Project full access'),
                'count' => __('Project count'),
                'tasks' => __('Project tasks'),
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
                'count' => __('Task count')
            ],
            'task-comment' => [
                'list' => __('Task comments list'),
                'create' => __('Task comment create'),
                'show' => __('Task comment show'),
                'remove' => __('Task comment remove'),
                'full_access' => __('Tasks comments full access'),
            ],
            'roles' => [
                'list' => __('Role list'),
                'create' => __('Role create'),
                'edit' => __('Role edit'),
                'show' => __('Role show'),
                'remove' => __('Role remove'),
                'allowed-rules' => __('Role allowed rule list'),
                'full_access' => __('Roles full access'),
                'count' => __('Role count'),
            ],
            'screenshots' => [
                'list' => __('Screenshot list'),
                'create' => __('Screenshot create'),
                'edit' => __('Screenshot edit'),
                'show' => __('Screenshot show'),
                'remove' => __('Screenshot remove'),
                'remove_related' => __('Remove screenshots of related users'),
                'bulk-create' => __('Screenshot multiple create'),
                'dashboard' => __('Screenshot list at dashboard'),
                'manager_access' => __('Screenshots manager access'),
                'full_access' => __('Screenshots full access'),
            ],
            'time-intervals' => [
                'list' => __('Time interval list'),
                'create' => __('Time interval create'),
                'edit' => __('Time interval edit'),
                'show' => __('Time interval show'),
                'remove' => __('Time interval remove'),
                'bulk-create' => __('Time interval multiple create'),
                'bulk-edit' => __('Time interval bulk edit'),
                'bulk-remove' => __('Time interval bulk remove'),
                'full_access' => __('Time intervals full access'),
                'manager_access' => __('Time intervals manager access'),
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
                'manager_access' => __('Users manager access'),
                'count' => __('User count'),
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
                'full_access' => __('Rules full access'),
            ],
            'project-report' => [
                'list' => __('Projects report list'),
                'projects' => __('Projects report related projects'),
                'manager_access' => __('Projects report manager access'),
                'screenshots' => __('Projects report task screenshots access'),
            ],
            'time-duration' => [
                'list' => __('Time duration list'),
            ],
            'time-use-report' => [
                'list' => __('Time use report list'),
                'manager_access' => __('Time use report manager access'),
            ],
            'dashboard' => [
                'manager_access' => __('Dashboard manager access'),
            ],
            'integration' => [
                'gitlab' => __('GitLab integration'),
                'jira' => __('Jira integration'),
            ],
        ]);
    }

}
