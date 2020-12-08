<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:make:admin {name : Administrator\'s name} {email : Administrator\'s email} '
    . '{password : Administrator\'s password} {--o : Skip instance registration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates admin user';

    public function handle(): void
    {
        if (!$this->option('o') && !User::where(['is_admin' => true])->count()) {
            $this->call('cattr:register', [
                'adminEmail' => $this->argument('email')
            ]);
        }

        $admin = User::create([
            'full_name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'url' => '',
            'company_id' => 1,
            'avatar' => '',
            'screenshots_active' => 1,
            'manual_time' => 0,
            'computer_time_popup' => 300,
            'blur_screenshots' => 0,
            'web_and_app_monitoring' => 1,
            'screenshots_interval' => 9,
            'active' => true,
            'password' => $this->argument('password'),
            'is_admin' => true,
            'role_id' => 2,
            'timezone' => 'Africa/Bamako',
            'language' => 'en'
        ]);

        $this->info("Administrator with email {$admin->email} was created successfully");
    }
}
