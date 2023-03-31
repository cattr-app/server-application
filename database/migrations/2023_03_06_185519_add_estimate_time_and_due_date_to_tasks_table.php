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
            $table->dateTime('due_date')->change();
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
        });
    }
};
