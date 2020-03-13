<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrelloTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trello_tasks_relation', function (Blueprint $table) {
            // Trello's task ID can be used as a primary key
            $table->string('id', 255)->primary();
            $table->unsignedInteger('task_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trello_tasks_relation');
    }
}
