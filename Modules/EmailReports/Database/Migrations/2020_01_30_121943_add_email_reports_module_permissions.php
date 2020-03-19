<?php

use App\Models\Role;
use App\Models\Rule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEmailReportsModulePermissions extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $allowedForRoles = ['root', 'observer', 'manager', 'client'];
        $roleIDs = Role::whereIn('name', $allowedForRoles)->get()->pluck('id');

        $object = 'email-reports';
        $map = [
            'list' => __('Email Reports list'),
            'show' => __('Email Reports show'),
            'edit' => __('Email Reports edit'),
            'remove' => __('Email Reports remove'),
            'create' => __('Email Reports create'),
            'count' => __('Email Reports count'),
        ];


        foreach ($map as $action => $name) {
            foreach ($roleIDs as $roleID) {
                Rule::updateOrCreate([
                    'role_id' => $roleID,
                    'object' => $object,
                    'action' => $action,
                ], ['allow' => 1]);
            }
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
