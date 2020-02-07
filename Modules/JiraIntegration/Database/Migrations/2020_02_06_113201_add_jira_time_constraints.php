<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddJiraTimeConstraints extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('jira_time_relation', function (Blueprint $table) {
            $table->foreign('jira_task_id')->references('id')->on('jira_tasks_relation')->onDelete('cascade');
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
        Schema::table('jira_time_relation', function (Blueprint $table) {
            $table->dropForeign(['jira_task_id']);
            $table->dropForeign(['time_interval_id']);
            $table->dropForeign(['user_id']);
        });
    }
}
