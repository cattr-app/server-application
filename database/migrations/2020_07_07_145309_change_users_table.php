<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                    'payroll_access',
                    'billing_access',
                    'permanent_tasks',
                    'webcam_shots',
                    'poor_time_popup'
                ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('poor_time_popup')->nullable();
            $table->integer('payroll_access')->nullable();
            $table->integer('billing_access')->nullable();
            $table->integer('permanent_tasks')->nullable();
            $table->integer('webcam_shots')->nullable();
        });
    }
}
