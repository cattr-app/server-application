<?php

namespace App\Http\Controllers\Api\v1;

use App\User;
use Illuminate\Http\JsonResponse;

/**
 * Class UserController
 *
 * @package App\Http\Controllers\Api\v1
 */
class UserController extends ItemController
{
    /**
     * @return string
     */
    public function getItemClass(): string
    {
        return User::class;
    }

    /**
     * @return array
     */
    public function getValidationRules(): array
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

    /**
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'user';
    }

    /**
     * @param array $requestData
     *
     * @return array
     */
    protected function filterRequestData(array $requestData): array
    {
        $requestData['password'] = bcrypt($requestData['password']);

        return $requestData;
    }
}

