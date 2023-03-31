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
        DB::statement($this->createView());
        $this->createMaterializedView();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::statement($this->dropView());
        Schema::dropIfExists('view_task_workers_materialized');
    }

    private function createView(): string
    {
        return <<<SQL
            CREATE VIEW view_task_workers AS
                SELECT
                  `i`.`user_id`,
                  `i`.`task_id`,
                  SUM(TIMESTAMPDIFF(SECOND, i.start_at, i.end_at)) as duration
                FROM
                  `time_intervals` as `i`
                  INNER JOIN `users` as `u` on `i`.`user_id` = `u`.`id`
                WHERE
                  `i`.`deleted_at` is null
                GROUP BY
                  `i`.`user_id`,
                  `i`.`task_id`
            SQL;
    }

    private function createMaterializedView(): void
    {
        Schema::create('view_task_workers_materialized', static function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('task_id');
            $table->unsignedInteger('duration');
            $table->integer('offset')->default(0);
            $table->boolean('created_by_cron')->default(false);

            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['user_id']);
            $table->index(['task_id']);
            $table->unique(['user_id', 'task_id']);
        });
    }

    private function dropView(): string
    {
        return <<<SQL
            DROP VIEW IF EXISTS `view_task_workers`;
            SQL;
    }
};
