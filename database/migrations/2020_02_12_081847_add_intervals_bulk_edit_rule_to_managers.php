<?php

use App\Models\Role;
use App\Models\Rule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIntervalsBulkEditRuleToManagers extends Migration
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
        $role = Role::where(['name' => 'manager'])->first();

        if (isset($role)) {
            $allow = [
                'time-intervals' => ['bulk-edit'],
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
        $role = Role::where(['name' => 'manager'])->first();

        if (isset($role)) {
            $disallow = [
                'time-intervals' => ['bulk-edit'],
            ];

            $this->updateRules($role->id, $disallow, false);
        }
    }
}
