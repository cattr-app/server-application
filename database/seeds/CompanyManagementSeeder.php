<?php

namespace Database\Seeders;

use App\Enums\ScreenshotsState;
use Illuminate\Database\Seeder;
use Settings;
use phpseclib3\Crypt\RSA;

class CompanyManagementSeeder extends Seeder
{
    public function run(): void
    {
        Settings::scope('core')->set('timezone', 'UTC', true);
        Settings::scope('core')->set('language', 'en', true);
        Settings::scope('core')->set('auto_thinning', true, true);
        Settings::scope('core')->set('screenshots_state', ScreenshotsState::REQUIRED->value, true);

        $privateKey =  RSA::createKey(2048);
        $publicKey = $privateKey->getPublicKey();
        Settings::scope('core.offline-sync')->set('private_key', $privateKey, true);
        Settings::scope('core.offline-sync')->set('public_key', $publicKey, true);
    }
}
