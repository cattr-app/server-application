<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUserLastTimeUsageView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('CREATE VIEW `user_time_activity` AS
            SELECT
                t2.id AS time_interval_id,
                t1.user_id,
                t2.task_id,
                t2.end_at AS last_time_activity
            FROM (
                SELECT
                    user_id
                FROM
                    time_intervals
                GROUP BY
                    user_id
                ) AS t1
            INNER JOIN
                time_intervals AS t2
            ON
                t2.id = (
                    SELECT id
                    FROM time_intervals t3
                    WHERE t1.user_id = t3.user_id
                    ORDER BY end_at DESC
                    LIMIT 1
             )');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP VIEW IF EXISTS `user_time_activity`');
    }
}
