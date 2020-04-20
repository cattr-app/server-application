<?php

use App\Models\Property;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class CompanyManagementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Model::unguard();

        Property::create([
            'entity_type' => Property::COMPANY_CODE,
            'entity_id' => 0,
            'name' => 'timezone',
            'value' => 'UTC'
        ]);

        Property::create([
            'entity_type' => Property::COMPANY_CODE,
            'entity_id' => 0,
            'name' => 'language',
            'value' => 'en'
        ]);
    }
}
