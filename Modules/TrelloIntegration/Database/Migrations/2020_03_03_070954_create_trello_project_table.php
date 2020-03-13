<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrelloProjectTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trello_projects_relation', function (Blueprint $table) {
            $table->string('id', 255)->primary();
            $table->unsignedInteger('project_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('trello_projects_relation');
    }
}
