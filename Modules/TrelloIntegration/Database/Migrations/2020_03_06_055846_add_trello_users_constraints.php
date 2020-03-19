<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddTrelloUsersConstraints extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trello_users_relation', static function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trello_users_relation', static function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
}
