<?php

use App\Models\Role;
use App\Models\Rule;
use Illuminate\Database\Migrations\Migration;

class AddRoleToUserRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $userRole = Role::where(['name' => 'user'])->first();

        if ($userRole) {
            Rule::updateOrCreate([
                'role_id' => $userRole->id,
                'object'  => 'time-intervals',
                'action'  => 'bulk-edit',
            ], ['allow' => true]);
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
