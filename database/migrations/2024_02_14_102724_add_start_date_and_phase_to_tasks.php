<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->date('start_date')->index('tasks_start_date_index')->after('estimate')->nullable();
            $table->foreignId('project_phase_id')->after('project_id')->nullable();
            $table->foreign('project_phase_id')->references('id')->on('project_phases')->nullOnDelete();
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_phase_id');
            $table->dropColumn(['start_date']);
            $table->dropIndex('tasks_due_date_index');
        });
    }
};
