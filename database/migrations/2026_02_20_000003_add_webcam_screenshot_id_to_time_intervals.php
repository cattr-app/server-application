<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('time_intervals', static function (Blueprint $table) {
            $table->uuid('webcam_screenshot_id')->nullable()->after('screenshot_id');
            $table->index('webcam_screenshot_id');
        });
    }

    public function down(): void
    {
        Schema::table('time_intervals', static function (Blueprint $table) {
            $table->dropIndex(['webcam_screenshot_id']);
            $table->dropColumn('webcam_screenshot_id');
        });
    }
};
