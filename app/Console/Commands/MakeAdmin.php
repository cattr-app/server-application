<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'cattr:make:admin {--o : Skip instance registration}';
    protected $description = 'Creates admin user';

    public function handle(): void
    {
        $email = $this->ask('What is your email?', 'admin@example.com');

        if (!$this->option('o') && !User::admin()->count()) {
            rescue(
                static fn() => $this->call(
                    RegisterInstance::class,
                    [
                        'adminEmail' => $email
                    ],
                ),
            );
        }

        User::factory()->admin()->create([
            'full_name' => $this->ask('What is your name?', 'Admin'),
            'email' => $email,
            'password' => $this->secret('What password should we set?'),
            'last_activity' => now(),
        ]);

        $this->info("Administrator with email $email was created successfully");
    }
}
