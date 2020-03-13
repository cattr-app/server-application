<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrelloTimeConstraints extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trello_time_relation', function (Blueprint $table) {
            $table->foreign('trello_task_id')->references('id')->on('trello_tasks_relation')->onDelete('cascade');
            $table->foreign('time_interval_id')->references('id')->on('time_intervals')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trello_time_relation', function (Blueprint $table) {
            $table->dropForeign(['trello_task_id']);
            $table->dropForeign(['time_interval_id']);
            $table->dropForeign(['user_id']);
        });
    }
}
