<?php

use App\Jobs\GenerateScreenshotThumbnail;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
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

        DB::table('screenshots')
            ->lazyById()
            ->each(static function ($screenshot) {
                $fileName = hash('sha256', $screenshot->time_interval_id) . '.jpg';

                if (!$screenshot->path) {
                    return;
                }

                if (Storage::exists($screenshot->path)) {
                    Storage::move($screenshot->path, 'screenshots/' . $fileName);
                }

                GenerateScreenshotThumbnail::dispatch($screenshot->time_interval_id);
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
};
