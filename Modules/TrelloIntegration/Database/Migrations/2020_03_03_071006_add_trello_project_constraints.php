<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrelloProjectConstraints extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trello_projects_relation', function (Blueprint $table) {
            // the external key's limitations provides the data ingertity
            // by not letting the ProjectRelation creation with the project missing from the system
            // and removing the ProjectRelation when the linked project is completely removed from the system
            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('trello_projects_relation', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
        });
    }
}
