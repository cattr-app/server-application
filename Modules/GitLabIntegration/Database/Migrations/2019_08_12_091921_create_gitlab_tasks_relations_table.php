<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGitlabTasksRelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gitlab_tasks_relations', function (Blueprint $table) {
            $table->unsignedBigInteger('gitlab_id');
            $table->unsignedBigInteger('task_id');

            $table->unique('gitlab_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gitlab_tasks_relations');
    }
}
