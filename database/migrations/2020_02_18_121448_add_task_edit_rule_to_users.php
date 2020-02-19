<?php

use App\Models\Role;
use App\Models\Rule;
use Illuminate\Database\Migrations\Migration;

class AddTaskEditRuleToUsers extends Migration
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
        $role = Role::where(['name' => 'user'])->first();

        if (isset($role)) {
            $allow = [
                'tasks' => ['edit', 'remove'],
            ];

            $this->updateRules($role->id, $allow, true);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $role = Role::where(['name' => 'user'])->first();

        if (isset($role)) {
            $disallow = [
                'tasks' => ['edit', 'remove'],
            ];

            $this->updateRules($role->id, $disallow, false);
        }
    }
}
