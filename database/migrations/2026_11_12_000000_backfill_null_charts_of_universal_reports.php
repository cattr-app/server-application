<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * `universal_reports.charts` is a NOT NULL json column, but the edit endpoint used to accept `charts: null` and
 * stored the JSON literal `null`. Charts are required now, so such reports get an empty list, which is what
 * clients send back after reading them.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('universal_reports')
            ->whereRaw("JSON_TYPE(charts) = 'NULL'")
            ->update(['charts' => '[]']);
    }

    public function down(): void
    {
        // The empty list is a valid value on its own; the lost JSON nulls are not restored.
    }
};
