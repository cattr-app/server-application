<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOldValueColumnToTaskHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('task_history', function (Blueprint $table) {
            $table->text('old_value')->nullable();
        });

        DB::table('task_history')
            ->orderBy('id')
            ->chunk(100, static function ($items) {
                foreach ($items as $item) {
                    $prevChange = DB::table('task_history')
                        ->where('task_id', $item->task_id)
                        ->where('field', $item->field)
                        ->where('created_at', '<', $item->created_at)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    if ($prevChange !== null) {
                        DB::table('task_history')
                            ->where('id', $item->id)
                            ->update(['old_value' => $prevChange->new_value]);
                    }
                }
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('task_history', function (Blueprint $table) {
            $table->dropColumn('old_value');
        });
    }
}
