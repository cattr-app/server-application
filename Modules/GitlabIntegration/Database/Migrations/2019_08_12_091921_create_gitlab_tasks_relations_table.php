<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGitlabTasksRelationsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gitlab_tasks_relations', static function (Blueprint $table) {
            $table->unsignedBigInteger('gitlab_id');
            $table->unsignedBigInteger('task_id');

            $table->unique('gitlab_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gitlab_tasks_relations');
    }
}
