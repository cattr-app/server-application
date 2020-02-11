<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModifireColumnsUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
            $table->string('url')->nullable()->change();
            $table->integer('company_id')->nullable()->change();
            $table->string('level')->nullable()->change();
            $table->integer('payroll_access')->nullable()->change();
            $table->integer('billing_access')->nullable()->change();
            $table->string('avatar')->nullable()->change();
            $table->integer('screenshots_active')->nullable()->change();
            $table->integer('manual_time')->nullable()->change();
            $table->integer('permanent_tasks')->nullable()->change();
            $table->integer('computer_time_popup')->nullable()->change();
            $table->string('poor_time_popup')->nullable()->change();
            $table->integer('blur_screenshots')->nullable()->change();
            $table->integer('web_and_app_monitoring')->nullable()->change();
            $table->integer('webcam_shots')->nullable()->change();
            $table->integer('screenshots_interval')->nullable()->change();
            $table->string('user_role_value')->nullable()->change();
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
            $table->string('first_name')->change();
            $table->string('last_name')->change();
            $table->string('url')->change();
            $table->integer('company_id')->change();
            $table->string('level')->change();
            $table->integer('payroll_access')->change();
            $table->integer('billing_access')->change();
            $table->string('avatar')->change();
            $table->integer('screenshots_active')->change();
            $table->integer('manual_time')->change();
            $table->integer('permanent_tasks')->change();
            $table->integer('computer_time_popup')->change();
            $table->string('poor_time_popup')->change();
            $table->integer('blur_screenshots')->change();
            $table->integer('web_and_app_monitoring')->change();
            $table->integer('webcam_shots')->change();
            $table->integer('screenshots_interval')->change();
            $table->string('user_role_value')->change();
        });
    }
}
