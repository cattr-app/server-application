<?php

use App\Models\Role;
use App\Models\Rule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoicesModulePermissions extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $allowedForRoles = ['root', 'manager'];
        $roleIDs = Role::whereIn('name', $allowedForRoles)->get()->pluck('id');

        foreach ($roleIDs as $roleID) {
            Rule::updateOrCreate([
                'role_id' => $roleID,
                'object' => 'invoices',
                'action' => 'list',
            ], ['allow' => 1]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // okay...
    }
}
