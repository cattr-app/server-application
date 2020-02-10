<?php

use App\Models\Role;
use App\Models\Rule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixesForRoles extends Migration
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
                'projects' => ['show'],
                'tasks' => ['show'],
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
                'projects' => ['show'],
                'tasks' => ['show'],
            ];

            $this->updateRules($userRole->id, $disallow, false);
        }
    }
}
