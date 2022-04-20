<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        DB::table('time_intervals')
            ->lazyById()
            ->each(static function ($interval) {
                DB::table('time_intervals')
                    ->where('id', $interval->id)
                    ->update([
                        'start_at' => Carbon::parse($interval->start_at)->setTimezone(
                            Settings::scope('core')->get('timezone')
                        ),
                        'end_at' => Carbon::parse($interval->end_at)->setTimezone(
                            Settings::scope('core')->get('timezone')
                        ),
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        DB::table('time_intervals')
            ->lazyById()
            ->each(static function ($interval) {
                DB::table('time_intervals')
                    ->where('id', $interval->id)
                    ->update([
                        'start_at' => Carbon::parse($interval->start_at)->setTimezone(config('app.timezone')),
                        'end_at' => Carbon::parse($interval->end_at)->setTimezone(config('app.timezone')),
                    ]);
            });
    }
};
