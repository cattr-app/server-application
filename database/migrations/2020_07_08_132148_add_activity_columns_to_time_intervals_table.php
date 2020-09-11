<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActivityColumnsToTimeIntervalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_intervals', function (Blueprint $table) {
            $table->integer('activity_fill')->nullable();
            $table->integer('mouse_fill')->nullable();
            $table->integer('keyboard_fill')->nullable();

            $table->dropColumn('count_mouse');
            $table->dropColumn('count_keyboard');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_intervals', function (Blueprint $table) {
            $table->dropColumn('activity_fill');
            $table->dropColumn('mouse_fill');
            $table->dropColumn('keyboard_fill');

            $table->integer('count_mouse');
            $table->integer('count_keyboard');
        });
    }
}
