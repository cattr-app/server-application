<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;

class LinkUploads extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Link uploads folder to public';

    /**
     * Execute the console command.
     *
     * @throws BindingResolutionException
     */
    public function handle(): void
    {
        if (file_exists(public_path('uploads'))) {
            $this->error('The [uploads] folder already exists');
            return;
        }

        $this->laravel->make('files')->link(
            storage_path('app/uploads'),
            public_path('uploads')
        );

        $this->info('The [public/uploads] folder has been linked sucessfully');
    }
}
