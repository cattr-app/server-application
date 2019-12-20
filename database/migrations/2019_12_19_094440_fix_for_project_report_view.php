<?php

use Illuminate\Database\Migrations\Migration;

class FixForProjectReportView extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared("
            CREATE OR REPLACE ALGORITHM=MERGE VIEW `project_report` AS
            SELECT
                `users`.id as `user_id`,
                `tasks`.id as `task_id`,
                `projects`.id as `project_id`,
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
            GROUP BY
                `date`,
                `user_id`,
                `task_id`,
                `project_id`
        ");
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
