<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('full_name');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email', 100)->unique();
            $table->string('url');
            $table->integer('company_id');
            $table->string('level');
            $table->integer('payroll_access');
            $table->integer('billing_access');
            $table->string('avatar');
            $table->integer('screenshots_active');
            $table->integer('manual_time');
            $table->integer('permanent_tasks');
            $table->integer('computer_time_popup');
            $table->string('poor_time_popup');
            $table->integer('blur_screenshots');
            $table->integer('web_and_app_monitoring');
            $table->integer('webcam_shots');
            $table->integer('screenshots_interval');
            $table->string('user_role_value');
            $table->string('active');
            $table->string('password');
            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
