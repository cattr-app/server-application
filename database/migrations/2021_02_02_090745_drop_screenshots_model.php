<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class DropScreenshotsModel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        if (!Storage::exists('screenshots/thumbs')) {
            Storage::makeDirectory('screenshots/thumbs');
        }

        DB::table('screenshots')->orderBy('id')->chunk(1000, static function ($screenshots) {
            foreach ($screenshots as $screenshot) {
                $fileName = hash('sha256', $screenshot->time_interval_id) . '.jpg';

                if (Storage::exists($screenshot->path)) {
                    Storage::move($screenshot->path, 'screenshots/' . $fileName);
                }

                if (Storage::exists($screenshot->thumbnail_path)) {
                    Storage::move($screenshot->thumbnail_path, 'screenshots/thumbs/' . $fileName);
                }
            }
        });

        Storage::deleteDirectory('uploads/screenshots');

        Schema::drop('screenshots');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        //
    }
}
