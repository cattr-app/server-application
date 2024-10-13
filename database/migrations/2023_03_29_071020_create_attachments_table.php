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
        Schema::create('attachments', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('attachmentable_id')->nullable()->index();
            $table->string('attachmentable_type')->nullable()->index();

            $table->string('original_name');
            $table->string('mime_type');
            $table->string('extension');
            $table->unsignedBigInteger('size');
            $table->char('hash', 64)->nullable();
//          TODO: decide between char and binary for hash
//            $table->char('hash', 32)->charset('binary')->index();

            $table->unsignedInteger('project_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->unsignedTinyInteger('status')->index();

            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->noActionOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->noActionOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
