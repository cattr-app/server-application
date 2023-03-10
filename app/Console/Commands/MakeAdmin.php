<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Console\Isolatable;

class MakeAdmin extends Command implements Isolatable
{
    protected $signature = 'cattr:make:admin
                            {--o : Skip instance registration}
                            {--e|email : user email}
                            {--n|name : user name}
                            {--p|password : user password}';
    protected $description = 'Creates admin user';

    public function handle(): void
    {
        do {
            $email = $this->option('email') ?: $this->ask('What is your email?', 'admin@example.com');

            $validator = Validator::make([
                'email' => $email
            ], [
                'email' => ['email', Rule::unique(User::class)]
            ]);

            $emailIsValid = !$validator->fails();

            if (!$emailIsValid) {
                $this->warn('Email is incorrect or it was already registered.');
            }
        } while (!$emailIsValid);


        if (!$this->option('o') && !User::admin()->count()) {
            $self = $this;
            rescue(
                static fn () => $self->call(
                    RegisterInstance::class,
                    [
                        'adminEmail' => $email
                    ],
                ),
            );
        }

        do {
            $password = $this->option('password') ?: $this->secret('What password should we set?');

            $validator = Validator::make([
                'password' => $password
            ], [
                'password' => 'min:6'
            ]);

            $passwordIsValid = !$validator->fails();

            if (!$passwordIsValid) {
                $this->warn('Minimum length is 6 characters.');
            }
        } while (!$passwordIsValid);

        User::factory()->admin()->create([
            'full_name' => $this->option('name') ?: $this->ask('What is your name?', 'Admin'),
            'email' => $email,
            'password' => $password,
            'last_activity' => now(),
        ]);

        $this->info("Administrator with email $email was created successfully");
    }
}
