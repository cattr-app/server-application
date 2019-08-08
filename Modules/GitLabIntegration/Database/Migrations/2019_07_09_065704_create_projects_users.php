<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsUsers extends Migration
{
    /**
     * @var string
     */
    const PROJECTS_TABLE = 'gitlab_projects';

    /**
     * @var string
     */
    const USERS_TABLE = 'users';

    /**
     * @var string
     */
    const PIVOT_TABLE = 'gitlab_projects_users';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(static::PIVOT_TABLE, function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id');
            $table->unsignedInteger('user_id');

            $table->unique(['project_id', 'user_id']);
            $table->foreign('project_id')->references('id')->on(static::PROJECTS_TABLE);
            $table->foreign('user_id')->references('id')->on(static::USERS_TABLE);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(static::PIVOT_TABLE);
    }
}
