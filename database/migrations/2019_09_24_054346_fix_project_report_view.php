<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixProjectReportView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('
            CREATE OR REPLACE VIEW `project_report` AS
            SELECT
                `time_intervals`.`user_id` as `user_id`,
                `users`.`full_name` as `user_name`,
                `time_intervals`.`task_id` as `task_id`,
                `tasks`.`project_id` as `project_id`,
                `tasks`.`task_name` as `task_name`,
                `projects`.`name` as `project_name`,
                `time_intervals`.`start_at` as `date`,
                SUM(TIME_TO_SEC(TIMEDIFF(`time_intervals`.`end_at`, `time_intervals`.`start_at`))) AS `duration`
            FROM
                `time_intervals`
            INNER JOIN
                `tasks`
            ON
                `tasks`.`id` = `time_intervals`.`task_id`
            INNER JOIN
                `projects`
            ON
                `tasks`.`project_id` = `projects`.`id`
            INNER JOIN
                `users`
            ON
                `users`.`id` = `time_intervals`.`user_id`
            WHERE 
                `time_intervals`.`deleted_at` is null
            GROUP BY
                `date`,
                `user_id`,
                `user_name`,
                `task_id`,
                `task_name`,
                `project_id`,
                `project_name`
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared( 'DROP VIEW IF EXISTS `project_report`' );
    }
}
