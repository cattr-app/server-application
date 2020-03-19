<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrelloTasksTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trello_tasks_relation', static function (Blueprint $table) {
            // Trello's task ID can be used as a primary key
            $table->string('id', 255)->primary();
            $table->unsignedInteger('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trello_tasks_relation');
    }
}
