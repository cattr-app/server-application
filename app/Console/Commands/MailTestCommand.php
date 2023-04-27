<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Mail;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'cattr:mail:test')]
class MailTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:mail:test {destination}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test mail sending';

    public function handle(): void
    {
        $destination = $this->argument('destination');

        Mail::raw('Text to e-mail', static function ($message) use ($destination) {
            $message->to($destination);
        });
    }
}
