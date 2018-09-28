<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Role;
use Auth;
use Filter;
use Route;
use Illuminate\Database\Eloquent\Builder;
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
     * @apiDefine UserModel
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
     * @apiParam {String}  [timezone]               User's timezone
     */

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
            'email'                  => 'required|unique:users,email',
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
     * @return string[]
     */
    public function getQueryWith(): array
    {
        return ['attached_users'];
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
     * @apiParam {String}   [timezone]              `QueryParam` User's timezone
     *
     * @apiSuccess (200) {User[]} UserList array of users objects
     */

    /**
     * @api {post} /api/v1/users/create Create
     * @apiDescription Create User Entity
     * @apiVersion 0.1.0
     * @apiName CreateUser
     * @apiGroup User
     *
     * @apiUse UserModel
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
     * @apiUse UserModel
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
        $idInt = is_int($request->get('id'));

        if (!$idInt) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'error' => 'Invalid id',
                    'reason' => 'Id is not integer',
                ]),
                400
            );
        }

        $validationRules = $this->getValidationRules();
        $validationRules['id'] = 'required';
        $validationRules['email'] .= ','.$request->get('id');
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

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(), ['id' => $request->get('id')]
            )
        );
        /** @var \Illuminate\Database\Eloquent\Model $item */
        $item = $itemsQuery->first();

        if (!$item) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'error' => 'User fetch fail',
                    'reason' => 'User not found',
                ]),
                400
            );
        }

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
     *
     * @apiUse DefaultDestroyRequestExample
     */

    /**
     * @api {post} /api/v1/users/bulk-edit bulkEdit
     * @apiDescription Editing Multiple Users
     * @apiVersion 0.1.0
     * @apiName bulkEditUsers
     * @apiGroup User
     *
     * @apiParam {Object[]} users                                 Array of objects User
     * @apiParam {Object}   users.object                          User object
     * @apiParam {Integer}  users.object.id                       User ID
     * @apiParam {String}   users.object.full_name                Full Name
     * @apiParam {String}   [users.object.first_name]             First Name
     * @apiParam {String}   [users.object.last_name]              Last Name
     * @apiParam {String}   users.object.email                    E-mail
     * @apiParam {String}   [users.object.url]                    ???
     * @apiParam {Integer}  [users.object.company_id]             ???
     * @apiParam {String}   [users.object.level]                  Role access level
     * @apiParam {Boolean}  [users.object.payroll_access]         ???
     * @apiParam {Boolean}  [users.object.billing_access]         ???
     * @apiParam {String}   [users.object.avatar]                 Avatar image url/uri
     * @apiParam {Boolean}  [users.object.screenshots_active]     Screenshots should be captured
     * @apiParam {Boolean}  [users.object.manual_time]            Allow manual time edit
     * @apiParam {Boolean}  [users.object.permanent_tasks]        ???
     * @apiParam {Boolean}  [users.object.computer_time_popup]    ???
     * @apiParam {Boolean}  [users.object.poor_time_popup]        ???
     * @apiParam {Boolean}  [users.object.blur_screenshots]       ???
     * @apiParam {Boolean}  [users.object.web_and_app_monitoring] ???
     * @apiParam {Boolean}  [users.object.webcam_shots]           ???
     * @apiParam {Integer}  [users.object.screenshots_interval]   Screenshots creation interval (seconds)
     * @apiParam {String}   [users.object.user_role_value]        ???
     * @apiParam {Boolean}  users.object.active                   User is active
     * @apiParam {Integer}  [users.object.role_id]                User's Role ID
     * @apiParam {String}   [users.object.timezone]               User's timezone
     *
     * @apiSuccess {Object[]} message        Array of User object
     * @apiSuccess {Object}   message.object User object
     *
     * @apiUse DefaultBulkEditErrorResponse
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

            if (!is_int($user['id'])) {
                return response()->json(
                    Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                        'error' => 'Invalid id',
                        'reason' => 'Id is not integer',
                    ]),
                    400
                );
            }

            $validationRules['email'] = 'required|unique:users,email,'.$user['id'];
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

            /** @var Builder $itemsQuery */
            $itemsQuery = Filter::process(
                $this->getEventUniqueName('answer.success.item.query.prepare'),
                $this->applyQueryFilter(
                    $this->getQuery(), ['id' => $user['id']]
                )
            );
            /** @var \Illuminate\Database\Eloquent\Model $item */
            $item = $itemsQuery->first();

            if (!$item) {
                return response()->json(
                    Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                        'error' => 'User fetch fail',
                        'reason' => 'User not found',
                    ]),
                    400
                );
            }

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


    /**
     * @api {post} /api/v1/users/relations Relations
     * @apiDescription Show attached users and to whom the user is attached
     * @apiVersion 0.1.0
     * @apiName RelationsUser
     * @apiGroup User
     *
     * @apiParam {Integer} [id]               User ID
     * @apiParam {Integer} [attached_user_id] Attached User ID
     *
     * @apiSuccess {Object[]} array        Array of User object
     * @apiSuccess {Object}   array.object User object
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function relations(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.relations'), $request->all());
        $full_access = Role::can(Auth::user(), 'users', 'full_access');
        $userId = false;
        $attachedId = false;

        if (isset($requestData['id'])) {
            $userId = is_int($requestData['id']) && $requestData['id'] > 0 ? $requestData['id'] : false;
        }

        if (isset($requestData['attached_user_id'])) {
            $attachedId = is_int($requestData['attached_user_id']) && $requestData['attached_user_id'] > 0 ? $requestData['attached_user_id'] : false;
        }

        if (!$userId && !$attachedId) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.error.item.relations'),
                [
                    'error' => 'Validation fail',
                    'reason' => 'id and attached_user_id is invalid',
                ]),
                400
            );
        }

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->getQuery()
        );
        $user = $itemsQuery->find($userId ? $userId : $attachedId);

        if (!$user) {
            return response()->json(Filter::process(
                $this->getEventUniqueName('answer.error.item.relations'),
                [
                    'error' => 'User not found',
                ]),
                400
            );
        }

        // if full_access user
        if ($full_access && Auth::user()->id === $userId) {
            /** @var User[] $rules */
            $users = User::where('id', '<>', $userId)->get();
        } else {
            /** @var User[] $rules */
            $user_users = $userId ? collect($user->attached_users) : collect($user->attached_to);
            $projects_users = [];
            $projects_related_users = [];

            if ($userId) {
                $projects = collect($user->projects);

                $projects_users = $projects->flatMap(function($project) {
                   return collect($project->users);
                })->unique('id');

                $project_ids = $projects->map(function ($project) { return $project->id; });
                $projects_related_users = User::whereHas('timeIntervals.task.project', function ($query) use ($project_ids) {
                    $query->whereIn('id', $project_ids);
                })->get();
            }
            $users = collect([$user_users, $projects_users, $projects_related_users])->collapse()->unique();
        }

        $users = collect($users)->filter(function($user, $key) use ($userId) {
           return $user->id !== $userId;
        });

        return response()->json(Filter::process(
            $this->getEventUniqueName('answer.success.item.relations'),
            $users
        ));
    }

    /**
     * @param bool $withRelations
     *
     * @return Builder
     */
    protected function getQuery($withRelations = true): Builder
    {
        $query = parent::getQuery($withRelations);
        $full_access = Role::can(Auth::user(), 'users', 'full_access');
        $relations_access = Role::can(Auth::user(), 'users', 'relations');
        $project_relations_access = Role::can(Auth::user(), 'projects', 'relations');
        $action_method = Route::getCurrentRoute()->getActionMethod();

        if ($full_access) {
            return $query;
        }

        $user_id = collect(Auth::user()->id);
        $users_id = collect([]);

        /** edit and remove only for directly related users */
        if ($action_method !== 'edit' && $action_method !== 'remove') {
            if ($project_relations_access) {
                $attached_user_id_to_project = collect(Auth::user()->projects)->flatMap(function ($project) {
                    $project_attached_user_ids = collect($project->users)->flatMap(function ($user) {
                        return collect($user->id);
                    });

                    $project_related_user_ids = collect($project->tasks)->flatMap(function ($task) {
                        return collect($task->timeIntervals)->flatMap(function ($interval) {
                            return collect($interval->user_id);
                        });
                    });

                    return collect([$project_attached_user_ids, $project_related_user_ids])->collapse()->unique();
                });

                $users_id = collect([$attached_user_id_to_project])->collapse();
            }
        }

        if ($relations_access) {
            $attached_users_id = collect(Auth::user()->attached_users)->flatMap(function($user) {
                return collect($user->id);
            });
            $users_id = collect([$users_id, $user_id, $attached_users_id])->collapse()->unique();
        } else {
            $users_id = collect([$users_id, $user_id])->collapse()->unique();
        }
        $query->whereIn('users.id', $users_id);

        return $query;
    }
}

