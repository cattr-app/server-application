<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (User::lazy() as $user) {
                if ($user->is_admin) {
                    $user->update(['role_id' => 0]);
                }
            }
            $table->dropColumn('is_admin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);

            foreach (User::lazy() as $user) {
                if ($user->role_id === 0) {
                    $user->update(['is_admin' => true]);
                }
            }
        });
    }
};
