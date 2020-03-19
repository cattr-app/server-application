<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJiraTaskConstraints extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('jira_tasks_relation', static function (Blueprint $table) {
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.

     */
    public function down(): void
    {
        Schema::table('jira_tasks_relation', static function (Blueprint $table) {
            $table->dropForeign(['task_id']);
        });
    }
}
