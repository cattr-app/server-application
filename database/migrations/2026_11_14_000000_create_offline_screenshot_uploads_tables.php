<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_screenshot_uploads', static function (Blueprint $table) {
            $table->id();
            // users.id is an unsigned INT
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->string('status', 16)->index();
            // Where the validated archive is kept until the job has finished; both are NULL once it is deleted
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->unsignedInteger('total');
            $table->timestamps();
            $table->timestamp('finished_at')->nullable();
        });

        // One row per processed archive entry: a retried job skips these entries.
        Schema::create('offline_screenshot_upload_items', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('upload_id')->constrained('offline_screenshot_uploads')->cascadeOnDelete();
            $table->string('entry_name');
            $table->unsignedInteger('user_id');
            $table->string('screenshot_id');
            $table->unsignedInteger('interval_id')->nullable();
            $table->boolean('success');
            // Translation key of the result message, translated for the reader
            $table->string('message');
            $table->timestamps();

            $table->unique(['upload_id', 'entry_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_screenshot_upload_items');
        Schema::dropIfExists('offline_screenshot_uploads');
    }
};
