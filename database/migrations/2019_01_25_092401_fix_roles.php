<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use App\Models\Rule;
use App\Models\Role;

class FixRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $manager_role_id = 5;
        $blocked_role_id = 255;

        $role_ids = Role::where('id', '<>', $blocked_role_id)
            ->pluck('id')
            ->toArray();

        if (in_array($manager_role_id, $role_ids)) {
            // Allows manager access to the role list, user list.
            $new_manager_rules = [
                ['roles', 'list'],
                ['users', 'manager_access'],
            ];
            foreach ($new_manager_rules as $rule) {
                [$object, $action] = $rule;
                Rule::withTrashed()->updateOrCreate([
                    'role_id' => $manager_role_id,
                    'object' => $object,
                    'action' => $action,
                ], [
                    'allow' => true,
                    'deleted_at' => null,
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
