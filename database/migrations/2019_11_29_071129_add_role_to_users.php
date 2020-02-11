<?php

use App\Models\Role;
use App\Models\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddRoleToUsers extends Migration
{
    protected function updateRules(int $roleID, array $rules, bool $allow)
    {
        foreach ($rules as $object => $actions) {
            foreach ($actions as $action) {
                Rule::updateOrCreate([
                    'role_id' => $roleID,
                    'object'  => $object,
                    'action'  => $action,
                ], ['allow' => $allow]);
            }
        }
    }

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

        $userRole = Role::where(['name' => 'user'])->first();
        if (isset($userRole)) {
            $allow = [
                'screenshots'    => ['create', 'bulk-create'],
                'time-intervals' => ['create', 'bulk-create'],
            ];
            $disallow = [
                'users'           => ['manager_access', 'list', 'show', 'create', 'edit', 'remove'],
                'projects'        => ['list', 'show', 'create', 'edit', 'remove'],
                'tasks'           => ['dashboard', 'list', 'show', 'create', 'edit', 'remove'],
                'screenshots'     => ['manager_access', 'dashboard', 'list', 'show', 'edit', 'remove'],
                'time-intervals'  => ['manager_access', 'list', 'show', 'edit', 'remove'],
                'dashboard'       => ['manager_access'],
                'project-report'  => ['manager_access'],
                'time-use-report' => ['manager_access'],
            ];

            $this->updateRules($userRole->id, $allow, true);
            $this->updateRules($userRole->id, $disallow, false);

            echo "\nUpdated user rules.\n";
        }

        $auditorRole = Role::where(['name' => 'auditor'])->first();
        if (isset($auditorRole)) {
            $allow = [
                'users' => ['manager_access', 'list', 'show'],
                'projects' => ['list', 'show'],
                'tasks' => ['dashboard', 'list', 'show', 'create'],
                'screenshots' => ['manager_access', 'dashboard', 'list', 'show', 'create', 'bulk-create'],
                'time-intervals' => ['manager_access', 'list', 'show', 'create', 'bulk-create'],
                'project-report' => ['manager_access'],
                'time-use-report' => ['manager_access'],
            ];
            $disallow = [
                'users' => ['create', 'edit', 'remove'],
                'projects' => ['create', 'edit', 'remove'],
                'tasks' => ['edit', 'remove'],
                'screenshots' => ['edit', 'remove'],
                'time-intervals' => ['edit', 'remove', 'bulk-remove'],
            ];

            $this->updateRules($auditorRole->id, $allow, true);
            $this->updateRules($auditorRole->id, $disallow, false);

            echo "\nUpdated auditor rules.\n";
        }

        $managerRole = Role::where(['name' => 'manager'])->first();
        if (isset($managerRole)) {
            $allow = [
                'users'           => ['manager_access', 'list', 'show', 'create', 'edit'],
                'projects'        => ['list', 'show', 'create', 'edit', 'remove'],
                'tasks'           => ['dashboard', 'list', 'show', 'create', 'edit', 'remove'],
                'screenshots'     => ['manager_access', 'dashboard', 'list', 'show', 'create', 'bulk-create', 'edit', 'remove'],
                'time-intervals'  => ['manager_access', 'list', 'show', 'create', 'bulk-create', 'edit', 'remove', 'bulk-remove'],
                'project-report'  => ['manager_access'],
                'time-use-report' => ['manager_access'],
            ];
            $disallow = [
                'users' => ['remove'],
            ];

            $this->updateRules($managerRole->id, $allow, true);
            $this->updateRules($managerRole->id, $disallow, false);

            echo "\nUpdated manager rules.\n";
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
