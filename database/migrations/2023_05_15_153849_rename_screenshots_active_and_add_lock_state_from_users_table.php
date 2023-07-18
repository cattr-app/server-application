<?php

use App\Models\User;
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
        DB::statement("ALTER TABLE users MODIFY COLUMN screenshots_active ENUM('-1', '0', '1', '2') DEFAULT '1'");

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('screenshots_active', 'screenshots_state');
            $table->boolean('screenshots_state_locked');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        User::where('screenshots_state', 2)->update(['screenshots_state' => 1]);

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('screenshots_state')->change();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('screenshots_state', 'screenshots_active');
            $table->dropColumn('screenshots_state_locked');
        });
    }
};
