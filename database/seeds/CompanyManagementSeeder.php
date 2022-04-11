<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Settings;

class CompanyManagementSeeder extends Seeder
{
    public function run(): void
    {
        if (!Settings::scope('core')->get('timezone')) {
            Settings::scope('core')->set('timezone', 'UTC');
        }
        if (!Settings::scope('core')->get('language')) {
            Settings::scope('core')->set('language', 'en');
        }
        if (!Settings::scope('core')->get('auto_thinning')) {
            Settings::scope('core')->set('auto_thinning', true);
        }

        Settings::scope('core')->set('installed', true);
    }
}
