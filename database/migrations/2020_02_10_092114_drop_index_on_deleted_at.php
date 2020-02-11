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
        Schema::table('screenshots', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('screenshots');

            if (array_key_exists('screenshots_deleted_at_index', $indexesFound)) {
                $table->dropIndex('screenshots_deleted_at_index');
            }
        });

        Schema::table('tasks', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $indexesFound = $sm->listTableIndexes('tasks');

            if (array_key_exists('tasks_deleted_at_index', $indexesFound)) {
                $table->dropIndex('tasks_deleted_at_index');
            }
        });
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
