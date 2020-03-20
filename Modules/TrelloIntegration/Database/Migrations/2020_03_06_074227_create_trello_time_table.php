<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrelloTimeTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trello_time_relation', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('trello_task_id', 255);
            $table->unsignedInteger('time_interval_id');
            $table->unsignedInteger('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trello_time_relation');
    }
}
