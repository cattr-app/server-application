<?php

namespace App\Http\Controllers\Api\v1;

use App\Models\Project;
use App\Models\Role;
use Auth;
use Filter;
use Event;
use Route;
use Illuminate\Database\Eloquent\Builder;
use Validator;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\InviteUser;

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
     * @apiParam {String}  email                    E-mail
     * @apiParam {String}  [url]                    ???
     * @apiParam {Integer} [company_id]             ???
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
     * @apiParam {Boolean} active                   Is User active
     * @apiParam {Integer} [role_id]                User Role id
     * @apiParam {String}  [timezone]               User timezone
     */


    public function __construct()
    {
        Filter::listen('request.item.create.user', static::class . '@' . 'requestUserCreateHook');
        Filter::listen('request.item.edit.user', static::class . '@' . 'requestUserCreateHook');

        Event::listen('item.edit.after.user', static::class . '@' . 'changePasswordHook');

        parent::__construct();
    }

    public function sendInviteHook($user, $requestData)
    {
        Mail::to($user->email)->send(new InviteUser($user->email, $requestData['password']));
        return $user;
    }

    public function changePasswordHook($user, $requestData)
    {
        if ($user->change_password && !is_null($requestData['password'])) {
            $user->change_password = false;
            $user->save();
        }
    }


    public function requestUserCreateHook($requestData)
    {
        $send_invite = $requestData['send_invite'] ?? 0;
        $change_password = $requestData['change_password'] ?? 0;

        if ($send_invite) {
            Event::listen('item.create.after.user', static::class . '@' . 'sendInviteHook');
            Event::listen('item.edit.after.user', static::class . '@' . 'sendInviteHook');
        } else {
            if ($change_password) {
                unset($requestData['change_password']);
            }
        }

        return $requestData;
    }


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
            'active'                 => 'required|boolean',
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
        return [
            'roles',
        ];
    }

    /**
     * @return array
     */
    public static function getControllerRules(): array
    {
        return [
            'index' => 'users.list',
            'count' => 'users.list',
            'create' => 'users.create',
            'edit' => 'users.edit',
            'show' => 'users.show',
            'destroy' => 'users.remove',
            'bulkEdit' => 'users.bulk-edit',
            'relations' => 'users.relations',
        ];
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
     * @apiParam {String}   [email]                 `QueryParam` E-mail
     * @apiParam {String}   [url]                   `QueryParam` ???
     * @apiParam {Integer}  [company_id]            `QueryParam` ???
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
     * @apiParam {Boolean}  [active]                             User is active
     * @apiParam {Integer}  [roles]                 `QueryParam` User's Roles
     * @apiParam {String}   [created_at]            `QueryParam` User Creation DateTime
     * @apiParam {String}   [updated_at]            `QueryParam` Last User data update DataTime
     * @apiParam {String}   [deleted_at]            `QueryParam` When User was deleted (null if not)
     * @apiParam {String}   [timezone]              `QueryParam` User's timezone
     *
     * @apiSuccess (200) {Object[]} Users
     */

    /**
     * @api {post} /api/v1/users/create Create
     * @apiDescription Create User Entity
     * @apiVersion 0.1.0
     * @apiName CreateUser
     * @apiGroup User
     *
     * @apiParamExample {json} Request Example
     * {
     *   "full_name": "John Doe",
     *   "email": "johndoe@example.com",
     *   "active": "1",
     *   "password": "secretpassword",
     *   "role_id": "3"
     * }
     *
     * @apiSuccess {Object} res User
     * @apiSuccess {Object} res.full_name   User
     * @apiSuccess {Object} res.email       Email
     * @apiSuccess {Object} res.active      Is user active
     * @apiSuccess {Object} res.roles       User roles
     * @apiSuccess {Object} res.updated_at  User last update datetime
     * @apiSuccess {Object} res.created_at  User registration datetime
     *
     *
     * @apiSuccessExample {json} Response Example
     * {
     *   "res": {
     *     "full_name": "John Doe",
     *     "email": "johndoe@example.com",
     *     "active": "1",
     *     "role_id": "1",
     *     "updated_at": "2018-10-18 09:06:36",
     *     "created_at": "2018-10-18 09:06:36",
     *     "id": 3
     *   }
     * }
     *
     * @apiUse UserModel
     */

    /**
     * Create item
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $requestData = Filter::process($this->getEventUniqueName('request.item.create'), $request->all());

        $validator = Validator::make(
            $requestData,
            Filter::process($this->getEventUniqueName('validation.item.create'), $this->getValidationRules())
        );

        if ($validator->fails()) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.create'), [
                    'error' => 'Validation fail',
                    'reason' => $validator->errors()
                ]),
                400
            );
        }

        $cls = $this->getItemClass();

        Event::dispatch($this->getEventUniqueName('item.create.before'), $requestData);

        $item = Filter::process(
            $this->getEventUniqueName('item.create'),
            $cls::create($this->filterRequestData($requestData))
        );

        if (isset($requestData['roles'])) {
            $item->syncRoles($requestData['roles']);
        }

        $item->save();

        Event::dispatch($this->getEventUniqueName('item.create.after'), [$item, $requestData]);

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.create'), [
                'res' => $item,
            ])
        );
    }

    /**
     * @api {post} /api/v1/users/show Show
     * @apiDescription Show User
     * @apiVersion 0.1.0
     * @apiName ShowUser
     * @apiGroup User
     *
     * @apiParam {Integer} id   User id
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiSuccess {Object}  object             User
     * @apiSuccess {Integer} object.id          User id
     * @apiSuccess {String}  object.full_name   User full name
     * @apiSuccess {String}  object.email       User email
     * @apiSuccess {String}  object.url         User url
     * @apiSuccess {Integer} object.role_id     User role id
     *
     * @apiSuccessExample {json} Response Example
     * {
     *   "id": 1,
     *   "full_name": "Admin",
     *   "email": "admin@example.com",
     *   "url": "",
     *   "company_id": 1,
     *   "payroll_access": 1,
     *   "billing_access": 1,
     *   "avatar": "",
     *   "screenshots_active": 1,
     *   "manual_time": 0,
     *   "permanent_tasks": 0,
     *   "computer_time_popup": 300,
     *   "poor_time_popup": "",
     *   "blur_screenshots": 0,
     *   "roles": { "id": 2, "name": "user", "deleted_at": null, "created_at": "2018-10-12 11:44:08", "updated_at": "2018-10-12 11:44:08" },
     *   "web_and_app_monitoring": 1,
     *   "webcam_shots": 0,
     *   "screenshots_interval": 9,
     *   "active": 1,
     *   "deleted_at": null,
     *   "created_at": "2018-10-18 09:36:22",
     *   "updated_at": "2018-10-18 09:36:22",
     *   "role_id": 1,
     *   "timezone": null,
     *  }
     *
     */

    /**
     * @api {put, post} /api/v1/users/edit Edit
     * @apiDescription Edit User
     * @apiVersion 0.1.0
     * @apiName EditUser
     * @apiGroup User
     *
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1,
     *   "full_name": "Jonni Tree",
     *   "email": "gook@tree.com",
     *   "active": "1"
     * }
     *
     * @apiSuccess {Object} res   User
     *
     * @apiSuccessExample {json} Response Example
     * {
     *   "res": {
     *      "id": 1,
     *      "full_name": "Jonni Tree",
     *       "email": "gook@tree.com",
     *       "url": "",
     *       "company_id": 1,
     *       "payroll_access": 1,
     *       "billing_access": 1,
     *       "avatar": "",
     *       "screenshots_active": 1,
     *       "manual_time": 0,
     *       "permanent_tasks": 0,
     *       "computer_time_popup": 300,
     *       "poor_time_popup": "",
     *       "blur_screenshots": 0,
     *       "web_and_app_monitoring": 1,
     *       "webcam_shots": 0,
     *       "screenshots_interval": 9,
     *       "roles": { "id": 2, "name": "user", "deleted_at": null, "created_at": "2018-10-12 11:44:08", "updated_at": "2018-10-12 11:44:08" },
     *       "active": "1",
     *       "deleted_at": null,
     *       "created_at": "2018-10-18 09:36:22",
     *       "updated_at": "2018-10-18 11:04:50",
     *       "role_id": 1,
     *       "timezone": null,
     *     }
     *   }
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

        if (isset($requestData['roles'])) {
            $item->syncRoles($requestData['roles']);
        }

        $item = Filter::process($this->getEventUniqueName('item.edit'), $item);
        $item->save();

        Event::dispatch($this->getEventUniqueName('item.edit.after'), [$item, $requestData]);

        return response()->json(
            Filter::process($this->getEventUniqueName('answer.success.item.edit'), [
                'res' => $item,
            ])
        );
    }

    /**
     * @api {delete, post} /api/v1/users/remove Destroy
     * @apiDescription Destroy User
     * @apiVersion 0.1.0
     * @apiName DestroyUser
     * @apiGroup User
     *
     * @apiSuccess {string} message User destroy status
     *
     * @apiSuccessExample {json} Response Example
     * {
     *   "message": "Item has been removed"
     * }
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
     * @apiParam {Object[]} users                                 Users
     * @apiParam {Object}   users.object                          User
     * @apiParam {Integer}  users.object.id                       User id
     * @apiParam {String}   users.object.full_name                Full Name
     * @apiParam {String}   users.object.email                    E-mail
     * @apiParam {String}   [users.object.url]                    ???
     * @apiParam {Integer}  [users.object.company_id]             ???
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
     * @apiParam {Boolean}  users.object.active                   User is active
     * @apiParam {Integer}  [users.object.role_id]                User Role id
     * @apiParam {String}   [users.object.timezone]               User timezone
     *
     * @apiSuccess {Object[]} message        Users
     * @apiSuccess {Object}   message.object User
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
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.bulkEdit'), [
                    'error' => 'validation fail',
                    'reason' => 'users is empty',
                ]),
                400
            );
        }

        $users = $requestData['users'];
        if (!is_array($users)) {
            return response()->json(
                Filter::process($this->getEventUniqueName('answer.error.item.bulkEdit'), [
                    'error' => 'validation fail',
                    'reason' => 'users should be an array',
                ]),
                400
            );
        }

        $validationRules = $this->getValidationRules();
        $validationRules['id'] = 'required';
        unset($validationRules['password']);

        foreach ($users as $user) {
            if (!isset($user['id']) || !is_int($user['id'])) {
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
     * @api {get, post} /api/v1/users/relations Relations
     * @apiDescription Show attached users and to whom the user is attached
     * @apiVersion 0.1.0
     * @apiName RelationsUser
     * @apiGroup User
     *
     * @apiErrorExample Wrong id
     * {
     *   "error": "Validation fail",
     *   "reason": "id and attached_user_id is invalid"
     * }
     *
     * @apiSuccessExample {json} Response example
     * {
     *    "": ""
     * }
     *
     * @apiParam {Integer} [id]               User id
     * @apiParam {Integer} [attached_user_id] Attached User id
     *
     * @apiSuccess {Object[]} array        Users
     * @apiSuccess {Object}   array.object User
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
            $users = collect([$projects_users, $projects_related_users])->collapse()->unique();
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
        /** @var User $user */
        $user = Auth::user();

        $query = parent::getQuery($withRelations);
        $full_access = $user->allowed('users', 'full_access');

        if ($full_access) {
            return $query;
        }

        $project_relations_access = $user->allowed('projects', 'relations');
        $action_method = Route::getCurrentRoute()->getActionMethod();

        $user_id = collect($user->id);
        $users_id = collect([]);

        /** edit and remove only for directly related users */
        if ($action_method !== 'edit' && $action_method !== 'remove') {
            if ($project_relations_access) {
                $projectIDs = $user->projects->map(static function ($project) {
                    return $project->id;
                });

                $usersAssignedToProjectsIDs = User::joinQuery()
                    ->whereInJoin('projectsRelation.project.id', $projectIDs)
                    ->pluck('users.id')
                ;

                $projectsUsersIDs = User::joinQuery()
                    ->whereInJoin('timeIntervals.task.project.id', $projectIDs)
                    ->pluck('users.id')
                ;

                $users_id = collect([$usersAssignedToProjectsIDs, $projectsUsersIDs])->collapse()->unique();
            }
        }

        $query->whereIn(
            'users.id',
            collect([$users_id, $user_id])->collapse()->unique()
        );

        return $query;
    }
}

