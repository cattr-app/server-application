<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrelloTasksConstraints extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trello_tasks_relation', function (Blueprint $table) {
            // the external key's limitations provides the data ingertity
            // by not letting the TaskRelation creation with the task missing from the system
            // and removing the TaskRelation when the linked task is completely removed from the system
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('trello_tasks_relation', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
        });
    }
}
