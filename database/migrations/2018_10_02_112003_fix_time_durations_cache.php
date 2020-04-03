<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixTimeDurationsCache extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Adds soft-delete handling.
        // Fixes error with an update affects time durations of all users.

        DB::unprepared('DROP TRIGGER IF EXISTS `time_durations_cache_insert_trigger`');
        DB::unprepared('DROP TRIGGER IF EXISTS `time_durations_cache_update_trigger`');
        DB::unprepared('DROP TRIGGER IF EXISTS `time_durations_cache_delete_trigger`');
        DB::unprepared('DROP VIEW IF EXISTS `time_durations`');

        DB::unprepared('CREATE VIEW `time_durations` AS
            SELECT
                DATE(`start_at`) AS `date`,
                SUM(TIME_TO_SEC(TIMEDIFF(`end_at`, `start_at`))) AS `duration`,
                `user_id`
            FROM `time_intervals`
            WHERE `deleted_at` IS NULL
            GROUP BY
                `date`,
                `user_id`');

        DB::unprepared('CREATE TRIGGER `time_durations_cache_insert_trigger` AFTER INSERT ON `time_intervals`
            FOR EACH ROW
            BEGIN
                DECLARE `interval` INT UNSIGNED;
                DECLARE `current_date` DATE;
                SET
                    `interval` = TIME_TO_SEC(TIMEDIFF(NEW.`end_at`, NEW.`start_at`)),
                    `current_date` = DATE(NEW.`start_at`);

                IF NEW.`deleted_at` IS NULL THEN
                    IF (SELECT 1
                        FROM `time_durations_cache`
                        WHERE `time_durations_cache`.`date` = `current_date`
                            AND `time_durations_cache`.`user_id` = NEW.`user_id`
                        ) = 1
                    THEN
                        UPDATE `time_durations_cache`
                        SET `duration` = `duration` + `interval`
                        WHERE `time_durations_cache`.`date` = `current_date`
                            AND `time_durations_cache`.`user_id` = NEW.`user_id`;
                    ELSE
                        INSERT INTO `time_durations_cache` (`date`, `duration`, `user_id`)
                        VALUES (`current_date`, `interval`, NEW.`user_id`);
                    END IF;
                END IF;
            END');

        DB::unprepared('CREATE TRIGGER `time_durations_cache_update_trigger` AFTER UPDATE ON `time_intervals`
            FOR EACH ROW
            BEGIN
                DECLARE `new_interval` INT UNSIGNED;
                DECLARE `old_interval` INT UNSIGNED;
                DECLARE `new_date` DATE;
                DECLARE `old_date` DATE;
                SET
                    `new_interval` = TIME_TO_SEC(TIMEDIFF(NEW.`end_at`, NEW.`start_at`)),
                    `old_interval` = TIME_TO_SEC(TIMEDIFF(OLD.`end_at`, OLD.`start_at`)),
                    `new_date` = DATE(NEW.`start_at`),
                    `old_date` = DATE(OLD.`start_at`);

                IF OLD.`deleted_at` IS NULL THEN
                    UPDATE `time_durations_cache`
                    SET `duration` = `duration` - `old_interval`
                    WHERE `date` = `old_date`
                        AND `user_id` = OLD.`user_id`;
                END IF;

                IF NEW.`deleted_at` IS NULL THEN
                    IF (SELECT 1
                        FROM `time_durations_cache`
                        WHERE `time_durations_cache`.`date` = `new_date`
                            AND `time_durations_cache`.`user_id` = NEW.`user_id`
                        ) = 1
                    THEN
                        UPDATE `time_durations_cache`
                        SET `duration` = `duration` + `new_interval`
                        WHERE `date` = `new_date`
                            AND `time_durations_cache`.`user_id` = NEW.`user_id`;
                    ELSE
                        INSERT INTO `time_durations_cache` (`date`, `duration`, `user_id`)
                        VALUES (`new_date`, `new_interval`, NEW.`user_id`);
                    END IF;
                END IF;
            END');

        DB::unprepared('CREATE TRIGGER `time_durations_cache_delete_trigger` AFTER DELETE ON `time_intervals`
            FOR EACH ROW
            BEGIN
                DECLARE `interval` INT UNSIGNED;
                DECLARE `current_date` DATE;
                SET
                    `interval` = TIME_TO_SEC(TIMEDIFF(OLD.`end_at`, OLD.`start_at`)),
                    `current_date` = DATE(OLD.`start_at`);

                IF OLD.`deleted_at` IS NULL THEN
                    UPDATE `time_durations_cache`
                    SET `duration` = `duration` - `interval`
                    WHERE `time_durations_cache`.`date` = `current_date`
                        AND `time_durations_cache`.`user_id` = OLD.`user_id`;
                END IF;
            END');

        //DB::unprepared('CALL time_durations_cache_refresh()');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Restores old view and triggers.
        // Copied from the 2018_09_11_100952_add_index.php

        DB::unprepared('DROP TRIGGER IF EXISTS `time_durations_cache_insert_trigger`');
        DB::unprepared('DROP TRIGGER IF EXISTS `time_durations_cache_update_trigger`');
        DB::unprepared('DROP TRIGGER IF EXISTS `time_durations_cache_delete_trigger`');
        DB::unprepared('DROP VIEW IF EXISTS `time_durations`');

        DB::unprepared('CREATE VIEW `time_durations` AS SELECT DATE(`start_at`) AS `date`, SUM(TIME_TO_SEC(TIMEDIFF(`end_at`, `start_at`))) AS `duration`, `user_id` FROM `time_intervals` GROUP BY `date`,`user_id` ');

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
            END');

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
            END');

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
            END');

        //DB::unprepared('CALL time_durations_cache_refresh()');
    }
}
