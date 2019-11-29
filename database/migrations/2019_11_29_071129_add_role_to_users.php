<?php

use App\Models\Role;
use App\Models\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRoleToUsers extends Migration
{
    protected function updateData()
    {
        $userRoles = DB::table('user_role')->select(['user_id', 'role_id'])->get();
        $total = $userRoles->count();

        foreach ($userRoles as $index => $userRole) {
            DB::table('users')->where('id', $userRole->user_id)->update(['role_id' => $userRole->role_id]);
            echo "\nSetting roles to users..." . ($index + 1) . "/" . $total;
            echo " (" . floor(($index + 1) / $total * 100.0) . "%)";
        }

        echo "\nAll roles set to users.\n";

        $auditorRole = Role::where(['name' => 'auditor'])->first();
        if (!isset($auditorRole)) {
            $auditorRole = Role::updateOrCreate(['name' => 'auditor']);

            $allow = [
                'dashboard' => [
                    'manager_access',
                ],
                'project-report' => [
                    'list',
                    'projects',
                    'manager_access',
                ],
                'projects' => [
                    'list',
                    'show',
                ],
                'roles' => [
                    'list',
                    'allowed-rules',
                ],
                'screenshots' => [
                    'dashboard',
                    'list',
                    'show',
                    'manager_access',
                ],
                'tasks' => [
                    'dashboard',
                    'list',
                    'show',
                ],
                'time' => [
                    'project',
                    'task',
                    'task-user',
                    'tasks',
                    'total'
                ],
                'time-duration' => [
                    'list',
                ],
                'time-intervals' => [
                    'list',
                    'edit',
                    'remove',
                    'bulk-remove',
                    'show',
                    'manager_access'
                ],
                'time-use-report' => [
                    'list',
                ],
                'users' => [
                    'list',
                    'relations',
                    'show',
                    'manager_access',
                ],
                'integration' => [
                    'gitlab',
                    'redmine',
                ],
            ];

            foreach ($allow as $object => $actions) {
                foreach ($actions as $action => $action_name) {
                    Rule::updateOrCreate([
                        'role_id' => $auditorRole->id,
                        'object' => $object,
                        'action' => !is_int($action) ? $action : $action_name,
                        'allow' => true,
                    ]);
                }
            }

            echo "\nCreated auditor role.\n";
        }
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('role_id')->default(2);
            $table->foreign('role_id')->references('id')->on('role');
        });

        Schema::table('projects_users', function (Blueprint $table) {
            $table->unsignedInteger('role_id')->default(2);
            $table->foreign('role_id')->references('id')->on('role');
        });

        $this->updateData();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });

        Schema::table('projects_users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
        });
    }
}
