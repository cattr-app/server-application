<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\StorageCleanerHelper;

class RotateScreenshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'screenshots:rotate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate screenshots';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        if (!StorageCleanerHelper::needThinning()) {
            $this->info('Time for thinning hasn\'t come');
            return;
        }

        $this->info('For thinning available ' . StorageCleanerHelper::countAvailableScreenshots() . ' screenshots');

        $spaceBefore = StorageCleanerHelper::getUsedSpace();

        $this->info('Started thinning...');

        StorageCleanerHelper::thin();

        $this->info('Totally freed ' . round(
            ($spaceBefore - StorageCleanerHelper::getUsedSpace()) / 1024 / 1024,
            3
        ) . 'MB');
    }
}
