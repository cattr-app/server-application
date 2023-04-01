<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Validator;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Console\Isolatable;

#[AsCommand(name: 'cattr:make:admin')]
class MakeAdmin extends Command implements Isolatable
{
    protected $signature = 'cattr:make:admin
                            {--o : Skip instance registration}
                            {--email= : User email}
                            {--name= : User name}
                            {--password= : User password}';

    protected $description = 'Creates admin user with data from ENV or input';

    public function handle(): int
    {
        if ($this->option('email')) {
            $email = $this->option('email');

            if (Validator::make([
                'email' => $email
            ], [
                'email' => ['email', Rule::unique(User::class)]
            ])->fails()) {
                $this->error('Email is incorrect or it was already registered');
                return 1;
            }
        }

        $email = env('APP_ADMIN_EMAIL', 'admin@cattr.app');

        if (Validator::make([
                'email' => $email
            ], [
                'email' => ['email', Rule::unique(User::class)]
            ])->fails()) {
            if (env('IMAGE_VERSION', false)) {
                $this->info('Admin already exists, skipping creation');
                return 0;
            }

            $this->error('Email is incorrect or it was already registered');
            return 1;
        }

        if ($email !== 'admin@cattr.app' && !$this->option('o') && !User::admin()->count()) {
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

        if ($this->option('password')) {
            $password = $this->option('password');

            if (Validator::make([
                'password' => $password
            ], [
                'password' => 'min:6'
            ])->fails()) {
                $this->warn('Minimum length is 6 characters');
                return 1;
            }
        }

        $password = env('APP_ADMIN_PASSWORD', 'password');

        if (Validator::make([
                'password' => $password
            ], [
                'password' => 'min:6'
            ])->fails()) {
            $this->warn('Minimum length is 6 characters');
            return 1;
        }

        User::factory()->admin()->create([
            'full_name' => $this->option('name') ?: env('APP_ADMIN_NAME', 'Admin'),
            'email' => $email,
            'password' => $password,
            'last_activity' => now(),
        ]);

        $this->info("Administrator with email $email was created successfully");

        return 0;
    }
}
