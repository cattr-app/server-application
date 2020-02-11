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

        DB::unprepared('CREATE OR REPLACE VIEW `time_durations` AS SELECT DATE(`start_at`) AS `date`, SUM(TIME_TO_SEC(TIMEDIFF(`end_at`, `start_at`))) AS `duration`, `user_id` FROM `time_intervals` GROUP BY `date`,`user_id` ');



        Schema::create('time_durations_cache', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date');
            $table->integer('duration')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->foreign('user_id')->references('id')->on('users');
        });


        DB::unprepared('CREATE TRIGGER `time_durations_cache_insert_trigger` AFTER INSERT ON `time_intervals`
           FOR EACH ROW
           BEGIN
                DECLARE `interval`  INT UNSIGNED;
                DECLARE `current_date`  DATE;
                SET
                    `interval` = TIME_TO_SEC(TIMEDIFF(NEW.`end_at`, NEW.`start_at`)),
                    `current_date` = DATE(NEW.`start_at`);

                IF (
                    SELECT 1
                    FROM `time_durations_cache`
                    WHERE
                        `time_durations_cache`.`date` = `current_date`
                        AND
                        `time_durations_cache`.`user_id` = NEW.`user_id`
                    ) = 1
                THEN
                    UPDATE `time_durations_cache`
                    SET `duration` = `duration` + `interval`
                    WHERE `date` = `current_date`;
                ELSE
                    INSERT INTO `time_durations_cache` (`date`, `duration`, `user_id`)
                    VALUES (`current_date`, `interval`, NEW.`user_id`);
                END IF;
           END'
       );


        DB::unprepared('CREATE TRIGGER `time_durations_cache_update_trigger` AFTER UPDATE ON `time_intervals`
           FOR EACH ROW
           BEGIN
                DECLARE `new_interval`  INT UNSIGNED;
                DECLARE `old_interval`  INT UNSIGNED;
                DECLARE `old_date`  DATE;
                DECLARE `new_date`  DATE;
                SET
                    `new_interval` = TIME_TO_SEC(TIMEDIFF(NEW.`end_at`, NEW.`start_at`)),
                    `old_interval` = TIME_TO_SEC(TIMEDIFF(OLD.`end_at`, OLD.`start_at`)),
                    `new_date` = DATE(NEW.`start_at`),
                    `old_date` = DATE(OLD.`start_at`);

                UPDATE `time_durations_cache`
                SET
                    `duration` = `duration` - `old_interval`
                WHERE
                    `date` = `old_date`
                    AND
                    `user_id` = OLD.`user_id`;


                IF (
                    SELECT 1
                    FROM `time_durations_cache`
                    WHERE
                        `time_durations_cache`.`date` = `new_date`
                        AND
                        `time_durations_cache`.`user_id` = NEW.`user_id`
                    ) = 1
                THEN
                    UPDATE `time_durations_cache`
                    SET `duration` = `duration` + `new_interval`
                    WHERE
                        `date` = `new_date`
                        AND
                        `time_durations_cache`.`user_id` = NEW.`user_id`;
                ELSE
                    INSERT INTO `time_durations_cache` (`date`, `duration`, `user_id`)
                    VALUES (`new_date`, `new_interval`, NEW.`user_id`);
                END IF;
           END'
       );


        DB::unprepared('CREATE TRIGGER `time_durations_cache_delete_trigger` AFTER DELETE ON `time_intervals`
           FOR EACH ROW
           BEGIN
                DECLARE `interval`  INT UNSIGNED;
                DECLARE `current_date`  DATE;
                SET
                    `interval` = TIME_TO_SEC(TIMEDIFF(OLD.`end_at`, OLD.`start_at`)),
                    `current_date` = DATE(OLD.`start_at`);

                UPDATE `time_durations_cache`
                SET `duration` = `duration` - `interval`
                WHERE `date` = `current_date`;
           END'
       );


        DB::unprepared('DROP PROCEDURE IF EXISTS `time_durations_cache_refresh`;');
        DB::unprepared('CREATE PROCEDURE `time_durations_cache_refresh` ()
            BEGIN
                DELETE FROM `time_durations_cache`;

                INSERT INTO `time_durations_cache` (`date`, `duration`, `user_id`)
                SELECT
                    `time_durations`.`date`,
                    `time_durations`.`duration`,
                    `time_durations`.`user_id`
                FROM `time_durations`;

            END
        ');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared( 'DROP TRIGGER IF EXISTS `time_durations_cache_insert_trigger`' );
        DB::unprepared( 'DROP TRIGGER IF EXISTS `time_durations_cache_update_trigger`' );
        DB::unprepared( 'DROP TRIGGER IF EXISTS `time_durations_cache_delete_trigger`' );
        DB::unprepared( 'DROP PROCEDURE IF EXISTS `time_durations_cache_refresh`' );

        Schema::dropIfExists('time_durations_cache');

        DB::unprepared( 'DROP VIEW IF EXISTS `time_durations`' );

        Schema::table('time_intervals', function (Blueprint $table) {
            $table->dropIndex('time_intervals_start_at_index');
            $table->dropIndex('time_intervals_end_at_index');
            $table->dropIndex('time_intervals_end_at_start_at_index');
        });
    }
}
