<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTrelloProjectTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trello_projects_relation', static function (Blueprint $table) {
            $table->string('id', 255)->primary();
            $table->unsignedInteger('project_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trello_projects_relation');
    }
}
