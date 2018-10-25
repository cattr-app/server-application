<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Role;
use App\Models\Rule;

class UpdateRules extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $user_role_id = 2;
        $manager_role_id = 5;
        $blocked_role_id = 255;

        $role_ids = Role::where('id', '<>', $blocked_role_id)
            ->pluck('id')
            ->toArray();

        if (in_array($user_role_id, $role_ids)) {
            // Allows users to edit time intervals.
            // (Needed to change tasks on programmer's dashboard.)
            Rule::updateOrCreate([
                'role_id' => $user_role_id,
                'object' => 'time-intervals',
                'action' => 'edit',
            ], [
                'allow' => true,
            ]);
        }

        if (in_array($manager_role_id, $role_ids)) {
            // Allows manager access to the dashboard,
            // project report, screenshots, time intervals.
            $new_manager_rules = [
                ['dashboard', 'manager_access'],
                ['project-report', 'manager_access'],
                ['screenshots', 'manager_access'],
                ['time-intervals', 'manager_access'],
            ];
            foreach ($new_manager_rules as $rule) {
                [$object, $action] = $rule;
                Rule::updateOrCreate([
                    'role_id' => $manager_role_id,
                    'object' => $object,
                    'action' => $action,
                ], [
                    'allow' => true,
                ]);
            }
        }

        // Allows all roles to show time use report and time duration.
        $new_rules = [
            ['time-use-report', 'list'],
            ['time-duration', 'list'],
        ];
        foreach ($role_ids as $role_id) {
            foreach ($new_rules as $rule) {
                [$object, $action] = $rule;
                Rule::updateOrCreate([
                    'role_id' => $role_id,
                    'object' => $object,
                    'action' => $action,
                ], [
                    'allow' => true,
                ]);
            }
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
