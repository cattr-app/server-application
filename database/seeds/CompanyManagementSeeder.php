<?php

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
        Model::unguard();

        $this->settings->set('timezone', 'UTC');
        $this->settings->set('language', 'en');
    }
}
