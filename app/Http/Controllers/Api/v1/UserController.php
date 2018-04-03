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

    /**
     * @api {post} /api/v1/users/list List
     * @apiDescription Get list of Users
     * @apiVersion 0.1.0
     * @apiName GetUserList
     * @apiGroup User
     *
     * @apiParam {Integer} [id] `QueryParam` User ID
     * @apiParam {String} [full_name] `QueryParam` Full Name
     * @apiParam {String} [first_name] `QueryParam` First Name
     * @apiParam {String} [last_name] `QueryParam` Last Name
     * @apiParam {String} [email] `QueryParam` E-mail
     * @apiParam {String} [url] `QueryParam` ???
     * @apiParam {Integer} [company_id] `QueryParam` ???
     * @apiParam {String} [level] `QueryParam` Role access level
     * @apiParam {Boolean} [payroll_access] ???
     * @apiParam {Boolean} [billing_access] ???
     * @apiParam {String} [avatar] `QueryParam` Avatar image url/uri
     * @apiParam {Boolean} [screenshots_active] Screenshots should be captured
     * @apiParam {Boolean} [manual_time] Allow manual time edit
     * @apiParam {Boolean} [permanent_tasks] ???
     * @apiParam {Boolean} [computer_time_popup] ???
     * @apiParam {Boolean} [poor_time_popup] ???
     * @apiParam {Boolean} [blur_screenshots] ???
     * @apiParam {Boolean} [web_and_app_monitoring] ???
     * @apiParam {Boolean} [webcam_shots] ???
     * @apiParam {Integer} [screenshots_interval] `QueryParam` Screenshots creation interval (seconds)
     * @apiParam {String} [user_role_value] `QueryParam` ???
     * @apiParam {Boolean} [active] User is active
     * @apiParam {Integer} [role_id] `QueryParam` User's Role ID
     * @apiParam {DateTime} [created_at] `QueryParam` User Creation DateTime
     * @apiParam {DateTime} [updated_at] `QueryParam` Last User data update DataTime
     * @apiParam {DateTime} [deleted_at] `QueryParam` When User was deleted (null if not)
     *
     * @apiSuccess (200) {User[]} UserList array of users objects
     */



    /**
     * @api {post} /api/v1/users/create Create
     * @apiDescription Create User
     * @apiVersion 0.1.0
     * @apiName CreateUser
     * @apiGroup User
     */

    /**
     * @api {post} /api/v1/users/show Show
     * @apiDescription Show User
     * @apiVersion 0.1.0
     * @apiName ShowUser
     * @apiGroup User
     */

    /**
     * @api {post} /api/v1/users/edit Edit
     * @apiDescription Edit User
     * @apiVersion 0.1.0
     * @apiName EditUser
     * @apiGroup User
     */

    /**
     * @api {post} /api/v1/users/destroy Destroy
     * @apiDescription Destroy User
     * @apiVersion 0.1.0
     * @apiName DestroyUser
     * @apiGroup User
     */
}

