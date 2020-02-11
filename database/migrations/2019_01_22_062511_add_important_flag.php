<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddImportantFlag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('screenshots', function (Blueprint $table) {
            $table->boolean('important')->default(false);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('important')->default(false);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('important')->default(false);
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->boolean('important')->default(false);
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
            $table->dropColumn('important');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('important');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('important');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('important');
        });
    }
}
