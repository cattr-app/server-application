<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class RecreateRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*DB::raw('SET FOREIGN_KEY_CHECKS = 0');
        DB::raw('TRUNCATE role');
        DB::raw('TRUNCATE rule');
        DB::raw('SET FOREIGN_KEY_CHECKS = 1');
        Artisan::call('db:seed', ['--class' => 'RoleSeeder']);*/
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
