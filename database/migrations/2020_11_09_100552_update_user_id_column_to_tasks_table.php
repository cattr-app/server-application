<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUserIdColumnToTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('tasks', 'user_id')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (!Schema::hasColumn('tasks', 'user_id')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->nullable(false)->change();
        });

        Schema::enableForeignKeyConstraints();
    }
}
