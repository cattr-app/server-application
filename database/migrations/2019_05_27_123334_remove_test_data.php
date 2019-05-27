<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class RemoveTestData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('projects')->whereIn('id', range(0, 5))->update(['deleted_at' => Carbon::now()]);
        DB::table('tasks')->whereIn('id', range(0, 75))->update(['deleted_at' => Carbon::now()]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('projects')->whereIn('id', range(0, 5))->update(['deleted_at' => null]);
        DB::table('tasks')->whereIn('id', range(0, 75))->update(['deleted_at' => null]);
    }
}
