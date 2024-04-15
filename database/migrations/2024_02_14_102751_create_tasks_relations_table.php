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
        Schema::create('tasks_relations', function (Blueprint $table) {
            $table->unsignedInteger('parent_id');
            $table->unsignedInteger('child_id');
            $table->string('label');
            $table->unsignedBigInteger('weight');

            $table->foreign('parent_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('child_id')->references('id')->on('tasks')->onDelete('cascade');

            $table->primary(['parent_id', 'child_id'], 'source_to_target_pk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks_relations');
    }
};
