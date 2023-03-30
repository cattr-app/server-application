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
            $table->unsignedBigInteger('attachmentable_id')->index();
            $table->unsignedBigInteger('attachmentable_type')->index();

            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->char('hash', 64);
//          TODO: decide between char and binary for hash
//            $table->char('hash', 32)->charset('binary')->index();

            $table->unsignedInteger('project_id');
            $table->unsignedInteger('user_id');
            $table->boolean('healthy');

            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
