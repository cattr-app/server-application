<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsAdministratorColumnToUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', static function (Blueprint $table) {
                $table->boolean('is_admin')->default(false);
            });
        }

        // Updating users
        $users = User::with('role')->get();
        foreach ($users as $user) {
            /** @var User $user */
            if ($user->role && $user->role->name === 'root') {
                $user->is_admin = true;
                $user->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', static function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
}
