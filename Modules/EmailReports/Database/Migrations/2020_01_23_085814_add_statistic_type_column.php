<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStatisticTypeColumn extends Migration
{
    const PROJECT_REPORT_STATISTIC = 1;
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('email_reports', 'statistic_type')) {
            Schema::table('email_reports', function (Blueprint $table) {
                // Check ReportsSender  AVAILABLE_STATISTIC_TYPES
                $table->integer('statistic_type')->default(self::PROJECT_REPORT_STATISTIC);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('email_reports', function (Blueprint $table) {
            $table->dropColumn('statistic_type');
        });
    }
}
