<?php

namespace App\Console\Commands;

use App\Models\Screenshot;
use App\Models\TimeInterval;
use Exception;
use Illuminate\Console\Command;
use Storage;
use Cache;

/**
 * Class EmulateWork
 */
class EmulateWork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:demo:emulate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cattr Emulation of the work (only for demo)';

    private int $intervalDuration = 300;

    protected array $protectedFiles = ['uploads/screenshots/.gitignore'];

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws Exception
     */
    public function handle()
    {
        $plan = Cache::get('usersPlan');

        if (!$plan) {
            $this->call('cattr:demo:plan');
            return 1;
        }

        $now = time();

        $timeFromBeginOfTheDay = ((int)date('H') * 60) + ((int)date('i'));

        foreach ($plan as $entry) {
            foreach ($entry['intervals'] as $interval) {
                if ($timeFromBeginOfTheDay >= $interval['start'] && $timeFromBeginOfTheDay <= $interval['end']) {
                    $this->createTimeInterval(
                        $interval['task'],
                        $entry['user'],
                        date('c', $now - $this->intervalDuration),
                        date('c', $now)
                    );
                }
            }
        }

        return 0;
    }

    /**
     * @param int $taskId
     * @param int $userId
     * @param string $startAt
     * @param string $endAt
     */
    protected function createTimeInterval(int $taskId, int $userId, string $startAt, string $endAt): void
    {
        $interval = TimeInterval::create([
            'task_id' => $taskId,
            'user_id' => $userId,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'is_manual' => 0,
            'count_mouse' => random_int(10, 62),
            'count_keyboard' => random_int(10, 62),
        ]);

        $this->seedScreenshot($interval);
    }

    /**
     * @param TimeInterval $interval
     */
    protected function seedScreenshot(TimeInterval $interval): void
    {
        $screenshots = array_diff(Storage::files('uploads/screenshots'), $this->protectedFiles);

        $path = $screenshots[array_rand($screenshots)];

        $thumbnail = str_replace('uploads/screenshots', 'uploads/screenshots/thumbs', $path);

        Screenshot::create([
            'time_interval_id' => $interval->id,
            'path' => $path,
            'thumbnail_path' => $thumbnail
        ]);
    }
}
