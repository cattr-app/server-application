<?php

use App\Models\Role;
use App\Models\Rule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInvoicesModulePermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $allowedForRoles = ['root', 'manager'];
        $roleIDs = Role::whereIn('name', $allowedForRoles)->get()->pluck('id');

        foreach ($roleIDs as $roleID) {
            Rule::updateOrCreate([
                'role_id' => $roleID,
                'object'  => 'invoices',
                'action'  => 'list',
            ], ['allow' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
