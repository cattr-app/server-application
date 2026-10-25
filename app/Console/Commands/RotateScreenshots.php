<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\StorageCleaner;
use Illuminate\Contracts\Container\BindingResolutionException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cattr:screenshots:rotate')]
class RotateScreenshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:screenshots:rotate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate screenshots';

    /**
     * Execute the console command.
     *
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        if (!StorageCleaner::isSupported()) {
            $this->info('Screenshots are on object storage: use bucket lifecycle rules instead of rotation');
            return;
        }

        if (!StorageCleaner::needThinning()) {
            $this->info('Time for thinning hasn\'t come');
            return;
        }

        $this->info('For thinning available ' . StorageCleaner::countAvailableScreenshots() . ' screenshots');

        $spaceBefore = StorageCleaner::getUsedSpace();

        $this->info('Started thinning...');

        StorageCleaner::thin();

        $this->info('Totally freed ' . round(
            ($spaceBefore - StorageCleaner::getUsedSpace()) / 1024 / 1024,
            3
        ) . 'MB');
    }
}
