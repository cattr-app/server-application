<?php

namespace Modules\CompanyManagement\Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class CompanyManagementDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        Model::unguard();

        // $this->call("OthersTableSeeder");
    }
}
