<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropIndexOnDeletedAt extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasIndex('screenshots', 'screenshots_deleted_at_index')) {
            Schema::table('screenshots', function (Blueprint $table) {
                $table->dropIndex('screenshots_deleted_at_index');
            });
        }

        if (Schema::hasIndex('tasks', 'tasks_deleted_at_index')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropIndex('tasks_deleted_at_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('screenshots', function (Blueprint $table) {
            $table->index('deleted_at');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('deleted_at');
        });
    }
}
