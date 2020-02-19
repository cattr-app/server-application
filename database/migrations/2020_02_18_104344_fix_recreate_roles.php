<?php

use Illuminate\Database\Migrations\Migration;

class FixRecreateRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::table('role')->count() > 0) {
            DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            DB::statement('TRUNCATE role');
            DB::statement('TRUNCATE rule');
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');

            Artisan::call('db:seed', ['--class' => 'RoleSeeder']);

            DB::statement('UPDATE users SET role_id = 2 WHERE role_id > 3');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
