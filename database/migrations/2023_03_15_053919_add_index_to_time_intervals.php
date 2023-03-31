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
        Schema::table('time_intervals', static function (Blueprint $table) {
            $table->index(['user_id', 'task_id', 'deleted_at'], 'time_intervals_index_for_workers_view');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('time_intervals', static function (Blueprint $table) {
            $table->dropIndex('time_intervals_index_for_workers_view');
        });
    }
};
