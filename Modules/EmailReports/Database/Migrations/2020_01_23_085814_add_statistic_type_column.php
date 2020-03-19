<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatisticTypeColumn extends Migration
{
    public const PROJECT_REPORT_STATISTIC = 1;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (!Schema::hasColumn('email_reports', 'statistic_type')) {
            Schema::table('email_reports', static function (Blueprint $table) {
                $table->integer('statistic_type')->default(self::PROJECT_REPORT_STATISTIC);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('email_reports', static function (Blueprint $table) {
            $table->dropColumn('statistic_type');
        });
    }
}
