<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGitlabIntervals extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gitlab_intervals_sync', static function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('time_interval_id');
            $table->boolean('is_synced');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gitlab_intervals_sync');
    }
}
