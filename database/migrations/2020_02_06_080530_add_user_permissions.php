<?php

use App\Models\Role;
use App\Models\Rule;
use Illuminate\Database\Migrations\Migration;

class AddUserPermissions extends Migration
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

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $userRole = Role::where(['name' => 'user'])->first();

        if (isset($userRole)) {
            $allow = [
                'time-intervals' => ['bulk-remove'],
                'tasks' => ['create'],
            ];

            $this->updateRules($userRole->id, $allow, true);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $userRole = Role::where(['name' => 'user'])->first();

        if (isset($userRole)) {
            $disallow = [
                'time-intervals' => ['bulk-remove'],
                'tasks' => ['create'],
            ];

            $this->updateRules($userRole->id, $disallow, false);
        }
    }
}
