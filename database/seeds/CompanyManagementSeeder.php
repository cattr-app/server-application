<?php

namespace Database\Seeders;

use App\Models\Property;
use App\Services\CoreSettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class CompanyManagementSeeder extends Seeder
{
    /**
     * @var CoreSettingsService
     */
    protected CoreSettingsService $settings;

    /**
     * CompanyManagementSeeder constructor.
     * @param CoreSettingsService $settings
     */
    public function __construct(CoreSettingsService $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (!$this->settings->get('timezone')) {
            $this->settings->set('timezone', 'UTC');
        }
        if (!$this->settings->get('language')) {
            $this->settings->set('language', 'en');
        }
        if (!$this->settings->get('auto_thinning')) {
            $this->settings->set('auto_thinning', true);
        }
    }
}
