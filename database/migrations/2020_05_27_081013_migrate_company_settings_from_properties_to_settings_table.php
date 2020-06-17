<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MigrateCompanySettingsFromPropertiesToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $companySettings = DB::table('properties')
            ->where('entity_type', 'company')
            ->whereIn('name', ['timezone', 'language', 'work_time', 'color'])
            ->get();

        foreach ($companySettings as $property) {
            DB::table('settings')->insert([
                'module_name' => 'core',
                'key' => $property->name,
                'value' => $property->value,
            ]);

            DB::table('properties')->delete($property->id);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $coreSettings = DB::table('settings')->where('module_name', 'core')->get();

        foreach ($coreSettings as $setting) {
            DB::table('properties')->insert([
                'entity_id' => 0,
                'entity_type' => 'company',
                'name' => $setting->key,
                'value' => $setting->value
            ]);

            DB::table('settings')->delete($setting->id);
        }
    }
}
