<?php

namespace App\Http\Controllers\Api\v1;

use Filter;
use Validator;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'email'                  => 'required',
            'active'                 => 'required',
            'password'               => 'required',
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
     * @apiParam {Integer}  [id]                    `QueryParam` User ID
     * @apiParam {String}   [full_name]             `QueryParam` Full Name
     * @apiParam {String}   [first_name]            `QueryParam` First Name
     * @apiParam {String}   [last_name]             `QueryParam` Last Name
     * @apiParam {String}   [email]                 `QueryParam` E-mail
     * @apiParam {String}   [url]                   `QueryParam` ???
     * @apiParam {Integer}  [company_id]            `QueryParam` ???
     * @apiParam {String}   [level]                 `QueryParam` Role access level
     * @apiParam {Boolean}  [payroll_access]                     ???
     * @apiParam {Boolean}  [billing_access]                     ???
     * @apiParam {String}   [avatar]                `QueryParam` Avatar image url/uri
     * @apiParam {Boolean}  [screenshots_active]                 Screenshots should be captured
     * @apiParam {Boolean}  [manual_time]                        Allow manual time edit
     * @apiParam {Boolean}  [permanent_tasks]                    ???
     * @apiParam {Boolean}  [computer_time_popup]                ???
     * @apiParam {Boolean}  [poor_time_popup]                    ???
     * @apiParam {Boolean}  [blur_screenshots]                   ???
     * @apiParam {Boolean}  [web_and_app_monitoring]             ???
     * @apiParam {Boolean}  [webcam_shots]                       ???
     * @apiParam {Integer}  [screenshots_interval]  `QueryParam` Screenshots creation interval (seconds)
     * @apiParam {String}   [user_role_value]       `QueryParam` ???
     * @apiParam {Boolean}  [active]                             User is active
     * @apiParam {Integer}  [role_id]               `QueryParam` User's Role ID
     * @apiParam {DateTime} [created_at]            `QueryParam` User Creation DateTime
     * @apiParam {DateTime} [updated_at]            `QueryParam` Last User data update DataTime
     * @apiParam {DateTime} [deleted_at]            `QueryParam` When User was deleted (null if not)
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
     *
     * @apiParam {Integer} id                       User ID
     * @apiParam {String}  full_name                Full Name
     * @apiParam {String}  [first_name]             First Name
     * @apiParam {String}  [last_name]              Last Name
     * @apiParam {String}  email                    E-mail
     * @apiParam {String}  [url]                    ???
     * @apiParam {Integer} [company_id]             ???
     * @apiParam {String}  [level]                  Role access level
     * @apiParam {Boolean} [payroll_access]         ???
     * @apiParam {Boolean} [billing_access]         ???
     * @apiParam {String}  [avatar]                 Avatar image url/uri
     * @apiParam {Boolean} [screenshots_active]     Screenshots should be captured
     * @apiParam {Boolean} [manual_time]            Allow manual time edit
     * @apiParam {Boolean} [permanent_tasks]        ???
     * @apiParam {Boolean} [computer_time_popup]    ???
     * @apiParam {Boolean} [poor_time_popup]        ???
     * @apiParam {Boolean} [blur_screenshots]       ???
     * @apiParam {Boolean} [web_and_app_monitoring] ???
     * @apiParam {Boolean} [webcam_shots]           ???
     * @apiParam {Integer} [screenshots_interval]   Screenshots creation interval (seconds)
     * @apiParam {String}  [user_role_value]        ???
     * @apiParam {Boolean} active                   User is active
     * @apiParam {Integer} [role_id]                User's Role ID
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function edit(Request $request): JsonResponse
    {
        $requestData = Filter::process(
            $this->getEventUniqueName('request.item.edit'),
            $request->all()
        );

        $validationRules = $this->getValidationRules();
        $validationRules['id'] = 'required';
        unset($validationRules['password']);

        $validator = Validator::make(
            $requestData,
            Filter::process(
                $this->getEventUniqueName('validation.item.edit'),
                $validationRules
            )
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'error' => 'validation fail',
                    'reason' => $validator->errors()
                ]),
                400
            );
        }

        $cls = $this->getItemClass();
        $itemId = $request->get('id');
        $item = $cls::findOrFail($itemId);

        if (isset($requestData['password'])) {
            $item->fill($this->filterRequestData($requestData));
        } else {
            $item->fill($requestData);
        }

        $item = Filter::process($this->getEventUniqueName('item.edit'), $item);
        $item->save();

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.edit'), [
                'res' => $item,
            ])
        );
    }

    /**
     * @api {post} /api/v1/users/destroy Destroy
     * @apiDescription Destroy User
     * @apiVersion 0.1.0
     * @apiName DestroyUser
     * @apiGroup User
     */

    /**
     * @api {post} /api/v1/users/bulk/edit bulkEdit
     * @apiDescription Editing Multiple Users
     * @apiVersion 0.1.0
     * @apiName bulkEditUsers
     * @apiGroup Rule
     *
     * @apiParam {Object[]} users                               Array of objects User
     * @apiParam {Object}   users.object                        User object
     * @apiParam {Integer}  users.object.id                     User ID
     * @apiParam {String}   users.object.full_name              Full Name
     * @apiParam {String}   users.object.first_name             First Name
     * @apiParam {String}   users.object.last_name              Last Name
     * @apiParam {String}   users.object.email                  E-mail
     * @apiParam {String}   users.object.url                    ???
     * @apiParam {Integer}  users.object.company_id             ???
     * @apiParam {String}   users.object.level                  Role access level
     * @apiParam {Boolean}  users.object.payroll_access         ???
     * @apiParam {Boolean}  users.object.billing_access         ???
     * @apiParam {String}   users.object.avatar                 Avatar image url/uri
     * @apiParam {Boolean}  users.object.screenshots_active     Screenshots should be captured
     * @apiParam {Boolean}  users.object.manual_time            Allow manual time edit
     * @apiParam {Boolean}  users.object.permanent_tasks        ???
     * @apiParam {Boolean}  users.object.computer_time_popup    ???
     * @apiParam {Boolean}  users.object.poor_time_popup        ???
     * @apiParam {Boolean}  users.object.blur_screenshots       ???
     * @apiParam {Boolean}  users.object.web_and_app_monitoring ???
     * @apiParam {Boolean}  users.object.webcam_shots           ???
     * @apiParam {Integer}  users.object.screenshots_interval   Screenshots creation interval (seconds)
     * @apiParam {String}   users.object.user_role_value        ???
     * @apiParam {Boolean}  users.object.active                 User is active
     * @apiParam {Integer}  [users.object.role_id]              User's Role ID
     *
     * @apiSuccess {Object[]} message        Array of User object
     * @apiSuccess {Object}   message.object User object
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function bulkEdit(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.bulkEdit'), $request->all());
        $result = [];

        if (empty($requestData['users'])) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.error.item.bulkEdit'), [
                'error' => 'validation fail',
                'reason' => 'users is empty'
            ]),
                400
            );
        }

        $validationRules = $this->getValidationRules();
        $validationRules['id'] = 'required';
        unset($validationRules['password']);

        foreach ($requestData['users'] as $user) {
            $validator = Validator::make(
                $user,
                Filter::process($this->getEventUniqueName('validation.item.bulkEdit'), $validationRules)
            );

            if ($validator->fails()) {
                $result[] = [
                    'error' => 'validation fail',
                    'reason' => $validator->errors(),
                    'code' => 400
                ];
                continue;
            }

            $cls = $this->getItemClass();
            $itemId = $user['id'];
            $item = $cls::findOrFail($itemId);

            if (isset($user['password'])) {
                $item->fill($this->filterRequestData($user));
            } else {
                $item->fill($user);
            }

            $item = Filter::process($this->getEventUniqueName('item.edit'), $item);
            $item->save();
            $result[] = $item;
        }

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.bulkEdit'), [
                'messages' => $result,
            ]
        ));
    }
}

