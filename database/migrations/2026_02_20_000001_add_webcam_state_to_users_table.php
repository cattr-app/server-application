<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->string('webcam_state')->nullable()->after('screenshots_state_locked');
            $table->boolean('webcam_state_locked')->default(false)->after('webcam_state');
        });
    }

    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn(['webcam_state', 'webcam_state_locked']);
        });
    }
};
