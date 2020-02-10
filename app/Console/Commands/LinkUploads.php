<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
     * @return mixed
     */
    public function handle()
    {
        if (file_exists(public_path('uploads'))) {
            return $this->error('The [uploads] folder already exists');
        }

        $this->laravel->make('files')->link(
            storage_path('app/uploads'), public_path('uploads')
        );

        return $this->info('The [public/uploads] folder has been linked sucessfully');
    }
}
