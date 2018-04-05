<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMouseAndKeyboardCountersToTimeInterval extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('time_intervals', function (Blueprint $table) {
            $table->integer('count_mouse');
            $table->integer('count_keyboard');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('time_intervals', function(Blueprint $table) {
            $table->dropColumn('count_mouse');
            $table->dropColumn('count_keyboard');
        });
    }
}
