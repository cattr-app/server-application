<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropTimeDurationsCache extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('time_durations_cache');
        DB::unprepared('DROP PROCEDURE IF EXISTS time_durations_cache_refresh');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('time_durations_cache', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date');
            $table->integer('duration')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->foreign('user_id')->references('id')->on('users');
        });

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
}
