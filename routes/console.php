<?php

use App\Console\Commands\CalculateEfficiency;
use App\Console\Commands\FindSusFiles;
use App\Console\Commands\RecreateCronTaskWorkers;
use App\Console\Commands\RotateScreenshots;
use App\Console\Commands\VerifyAttachments;
use App\Jobs\ClearExpiredApps;
use Illuminate\Support\Facades\Schedule;

// The setting is read lazily, when the scheduler evaluates the event, as the pre-11 console kernel did:
// this file is loaded on every artisan call, including before the settings table exists.
Schedule::command(RotateScreenshots::class)->weekly()->when(static fn () => Settings::scope('core')->get('auto_thinning'));

Schedule::job(new ClearExpiredApps)->daily();

Schedule::command(RecreateCronTaskWorkers::class)->daily()->runInBackground()->withoutOverlapping();

Schedule::command(VerifyAttachments::class)->daily()->runInBackground()->withoutOverlapping();
Schedule::command(CalculateEfficiency::class)->daily()->runInBackground()->withoutOverlapping();

Schedule::command(FindSusFiles::class)->weekly()->runInBackground()->withoutOverlapping();
