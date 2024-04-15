<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('tasks', static function (Blueprint $table) {
            $table->unsignedInteger('estimate')->nullable();
            $table->date('due_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('tasks', static function (Blueprint $table) {
            $table->dropColumn('estimate');
            $table->dropColumn('due_date');
        });
    }
};
