<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJiraTimeConstraints extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jira_time_relation', static function (Blueprint $table) {
            $table->foreign('jira_task_id')->references('id')->on('jira_tasks_relation')->onDelete('cascade');
            $table->foreign('time_interval_id')->references('id')->on('time_intervals')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('jira_time_relation', static function (Blueprint $table) {
            $table->dropForeign(['jira_task_id']);
            $table->dropForeign(['time_interval_id']);
            $table->dropForeign(['user_id']);
        });
    }
}
