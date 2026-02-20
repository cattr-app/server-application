<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', static function (Blueprint $table) {
            $table->string('webcam_state')->nullable()->after('screenshots_state');
        });
    }

    public function down(): void
    {
        Schema::table('projects', static function (Blueprint $table) {
            $table->dropColumn('webcam_state');
        });
    }
};
