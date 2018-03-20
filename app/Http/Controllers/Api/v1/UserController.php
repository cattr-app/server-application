<?php

namespace App\Http\Controllers\Api\v1;

use App\User;

class UserController extends ItemController
{
    function getItemClass()
    {
        return User::class;
    }

    function getValidationRules()
    {
        return [
            'full_name'              => 'required',
            'first_name'             => 'required',
            'last_name'              => 'required',
            'email'                  => 'required',
            'url'                    => 'required',
            'company_id'             => 'required',
            'level'                  => 'required',
            'payroll_access'         => 'required',
            'billing_access'         => 'required',
            'avatar'                 => 'required',
            'screenshots_active'     => 'required',
            'manual_time'            => 'required',
            'permanent_tasks'        => 'required',
            'computer_time_popup'    => 'required',
            'poor_time_popup'        => 'required',
            'blur_screenshots'       => 'required',
            'web_and_app_monitoring' => 'required',
            'webcam_shots'           => 'required',
            'screenshots_interval'   => 'required',
            'user_role_value'        => 'required',
            'active'                 => 'required',
            'password'               => 'required',
            'role_id'                => 'required',
        ];
    }

    function getEventUniqueNamePart()
    {
        return 'user';
    }
}

