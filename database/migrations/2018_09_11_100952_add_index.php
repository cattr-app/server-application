<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndex extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_intervals', function (Blueprint $table) {
            $table->index('start_at');
            $table->index('end_at');
            $table->index(['end_at', 'start_at']);
        });

        DB::statement('CREATE VIEW `time_durations` AS SELECT DATE(`start_at`) AS `date`, SUM(TIME_TO_SEC(TIMEDIFF(`end_at`, `start_at`))) AS `duration`, `user_id` FROM `time_intervals` GROUP BY `date`,`user_id` ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_intervals', function (Blueprint $table) {
            $table->dropIndex('time_intervals_start_at_index');
            $table->dropIndex('time_intervals_end_at_index');
            $table->dropIndex('time_intervals_end_at_start_at_index');
        });
        DB::statement( 'DROP VIEW `time_durations`' );
    }
}
