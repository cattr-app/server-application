<?php

use App\Models\Rule;
use Illuminate\Database\Migrations\Migration;

class RemoveUserRoleListRule extends Migration
{
    const USER_ROLE_ID = 2;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Rule::withTrashed()->updateOrCreate([
            'role_id' => static::USER_ROLE_ID,
            'object' => 'roles',
            'action' => 'list',
        ], [
            'allow' => false,
            'deleted_at' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Rule::withTrashed()->updateOrCreate([
            'role_id' => static::USER_ROLE_ID,
            'object' => 'roles',
            'action' => 'list',
        ], [
            'allow' => true,
            'deleted_at' => null,
        ]);
    }
}
