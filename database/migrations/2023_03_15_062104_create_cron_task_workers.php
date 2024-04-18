<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('cron_task_workers', static function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id')->index();
            $table->unsignedInteger('task_id')->index();
            $table->unsignedInteger('duration');
            $table->integer('offset')->default(0);
            $table->boolean('created_by_cron')->default(false);

            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['user_id', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('cron_task_workers');
    }
};
