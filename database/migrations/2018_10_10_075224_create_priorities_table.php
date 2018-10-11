<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreatePrioritiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Remove priority column from the tasks table.
        if (Schema::hasTable('tasks')
            && Schema::hasColumn('tasks', 'priority')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropColumn('priority');
            });
        }

        // Add priorities table.
        if (!Schema::hasTable('priorities')) {
            Schema::create('priorities', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
            });

            DB::table('priorities')->insert([
                ['id' => 1, 'name' => 'Low'],
                ['id' => 2, 'name' => 'Normal'],
                ['id' => 3, 'name' => 'High'],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Add priority column to the tasks table.
        if (Schema::hasTable('tasks')
            && !Schema::hasColumn('tasks', 'priority')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->string('priority');
            });
        }

        // Remove priorities table.
        Schema::dropIfExists('priorities');
    }
}
