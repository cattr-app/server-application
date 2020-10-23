<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateTasksUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('tasks')
            ->select(['id', 'user_id'])
            ->where('user_id', '!=', 0)
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunk(100, static function ($tasks) {
                $array = [];
                foreach ($tasks as $task) {
                    $array[] = [
                        'task_id' => $task->id,
                        'user_id' => $task->user_id,
                    ];
                }

                DB::table('tasks_users')->insert($array);
            });

        Schema::table('tasks', static function (Blueprint $table) {
            try {
                $table->dropForeign('tasks_user_id_foreign');
            } catch(Exception $e) {}

            $table->dropColumn('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('tasks', static function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
        });

        Schema::enableForeignKeyConstraints();

        DB::table('tasks_users')
            ->select(['task_id', 'user_id'])
            ->orderBy('task_id')
            ->orderBy('user_id')
            ->chunk(100, static function ($taskUserRelations) {
                foreach ($taskUserRelations as $taskUserRelation) {
                    DB::table('tasks')
                        ->where(['id' => $taskUserRelation->task_id])
                        ->update(['user_id' => $taskUserRelation->user_id]);
                }
            });
    }
}
