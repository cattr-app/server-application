<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjects extends Migration
{
    /**
     * @var string
     */
    const TABLE = 'gitlab_projects';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(static::TABLE, function (Blueprint $table) {
            $table->increments('id');
            $table->string('gitlab_url');
            $table->unsignedInteger('gitlab_project_id');
            $table->string('name');

            $table->unique(['gitlab_url', 'gitlab_project_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(static::TABLE);
    }
}
