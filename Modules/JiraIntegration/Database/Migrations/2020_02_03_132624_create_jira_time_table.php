<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJiraTimeTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jira_time_relation', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('jira_task_id');
            $table->unsignedInteger('time_interval_id');
            $table->unsignedInteger('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jira_time_relation');
    }
}
