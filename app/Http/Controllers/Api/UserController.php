<?php

namespace App\Http\Controllers\Api;

use App;
use Carbon\Carbon;
use Exception;
use Filter;
use App\Mail\UserCreated;
use App\Models\ProjectsUsers;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Auth;
use Event;
use Mail;
use Route;
use App\Http\Requests\User\EditUserRequest;
use App\Http\Requests\User\SendInviteUserRequest;
use Illuminate\Support\Str;

class UserController extends ItemController
{
    /**
     * Get the controller rules.
     *
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
            'sendInvite' => 'users.edit',
        ];
    }

    /**
     * Get the validation rules.
     *
     * @return array
     */
    public function getValidationRules(): array
    {
        return [
            'full_name' => 'required',
            'email' => 'required|unique:users,email',
            'active' => 'required|boolean',
            'password' => 'sometimes|required|min:6',
            'screenshots_interval' => 'sometimes|integer|min:1|max:15',
        ];
    }

    /**
     * Get the model class.
     *
     * @return string
     */
    public function getItemClass(): string
    {
        return User::class;
    }

    /**
     * Get the event unique name part.
     *
     * @return string
     */
    public function getEventUniqueNamePart(): string
    {
        return 'user';
    }

    /**
     * @api             {get, post} /v1/users/list List
     * @apiDescription  Get list of Users with any params
     *
     * @apiVersion      1.0.0
     * @apiName         GetUserList
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_list
     * @apiPermission   users_full_access
     *
     * @apiUse          UserParams
     * @apiUse          UserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  [
     *    {
     *      "id": 1,
     *      "full_name": "Admin",
     *      "email": "admin@example.com",
     *      "url": "",
     *      "company_id": 1,
     *      "payroll_access": true,
     *      "billing_access": true,
     *      "avatar": "",
     *      "screenshots_active": 1,
     *      "manual_time": 0,
     *      "permanent_tasks": false,
     *      "computer_time_popup": 300,
     *      "poor_time_popup": 0,
     *      "blur_screenshots": false,
     *      "web_and_app_monitoring": true,
     *      "webcam_shots": false,
     *      "screenshots_interval": 9,
     *      "active": 1,
     *      "deleted_at": null,
     *      "created_at": "2019-11-04T10:01:50+00:00",
     *      "updated_at": "2019-11-04T10:01:50+00:00",
     *      "timezone": null,
     *      "important": 0,
     *      "change_password": 0,
     *      "is_admin": 0,
     *      "role_id": 1
     *    },
     *    {
     *      "id": 2,
     *      "full_name": "Darwin",
     *      "email": "darwin@seleondar.ru",
     *      "url": null,
     *      "company_id": null,
     *      "payroll_access": null,
     *      "billing_access": null,
     *      "avatar": null,
     *      "screenshots_active": 1,
     *      "manual_time": 1,
     *      "permanent_tasks": null,
     *      "computer_time_popup": 5000,
     *      "poor_time_popup": null,
     *      "blur_screenshots": null,
     *      "web_and_app_monitoring": null,
     *      "webcam_shots": null,
     *      "screenshots_interval": 5,
     *      "active": 1,
     *      "deleted_at": null,
     *      "created_at": "2019-11-04T10:22:20+00:00",
     *      "updated_at": "2019-11-06T10:42:25+00:00",
     *      "timezone": "Asia\/Omsk",
     *      "important": 0,
     *      "change_password": 0,
     *      "is_admin": 0,
     *      "role_id": 2
     *    }
     *  ]
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */

    /**
     * @api             {post} /v1/users/create Create
     * @apiDescription  Create User Entity
     *
     * @apiVersion      1.0.0
     * @apiName         CreateUser
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_create
     * @apiPermission   users_full_access
     *
     * @apiParam {String}   email      New user email
     * @apiParam {String}   full_name  New user name
     * @apiParam {String}   password   New user password
     * @apiParam {Integer}  active     Will new user be active or not `(1 - active, 0 - not)`
     * @apiParam {Integer}  role_id    ID of the role of the new user
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
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Object}   res      User
     *
     * @apiUse UserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "res": {
     *      "full_name": "John Doe",
     *      "email": "johndoe@example.com",
     *      "active": "1",
     *      "role_id": "1",
     *      "updated_at": "2018-10-18 09:06:36",
     *      "created_at": "2018-10-18 09:06:36",
     *      "id": 3
     *    }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ForbiddenError
     */
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);
        Event::listen($this->getEventUniqueName('item.create.after'), static::class . '@' . 'saveRelations');
        return parent::create($request);
    }

    /**
     * @api             {post} /v1/users/edit Edit
     * @apiDescription  Edit User
     *
     * @apiVersion      1.0.0
     * @apiName         EditUser
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_edit
     * @apiPermission   users_full_access
     *
     * @apiUse UserParams
     *
     * @apiParam {Integer}  id  ID of the target user
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1,
     *   "full_name": "Jonni Tree",
     *   "email": "gook@tree.com",
     *   "active": "1"
     * }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {Object}   res      User
     *
     * @apiUse UserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "res": {
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
     *       "role": { "id": 2, "name": "user", "deleted_at": null,
     *                 "created_at": "2018-10-12 11:44:08", "updated_at": "2018-10-12 11:44:08" },
     *       "active": "1",
     *       "deleted_at": null,
     *       "created_at": "2018-10-18 09:36:22",
     *       "updated_at": "2018-10-18 11:04:50",
     *       "role_id": 1,
     *       "timezone": null,
     *       "user_language": "en"
     *      }
     *  }
     *
     * @apiUse         400Error
     * @apiUse         ValidationError
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     */
    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function edit(Request $request): JsonResponse
    {
        $requestData = app(EditUserRequest::class)->validated();

        $requestData = Filter::process(
            $this->getEventUniqueName('request.item.edit'),
            $requestData
        );

        /** @var Builder $itemsQuery */
        $itemsQuery = Filter::process(
            $this->getEventUniqueName('answer.success.item.query.prepare'),
            $this->applyQueryFilter(
                $this->getQuery(),
                ['id' => $requestData['id']]
            )
        );
        /** @var Model $item */
        $item = $itemsQuery->first();

        if (!$item) {
            return new JsonResponse(
                Filter::process($this->getEventUniqueName('answer.error.item.edit'), [
                    'success' => false,
                    'error_type' => 'query.item_not_found',
                    'message' => 'User not found',
                ]),
                404
            );
        }

        if (App::environment('demo')) {
            unset($requestData['password']);
        }

        $item = Filter::process($this->getEventUniqueName('item.edit'), $item);

        $item->update($requestData);

        Event::dispatch($this->getEventUniqueName('item.edit.after'), [$item, $requestData]);

        $item = $this->saveRelations($item, $requestData);

        return new JsonResponse(
            Filter::process($this->getEventUniqueName('answer.success.item.edit'), [
                'success' => true,
                'res' => $item,
            ])
        );
    }

    /**
     * @api             {get, post} /v1/users/show Show
     * @apiDescription  Show User
     *
     * @apiVersion      1.0.0
     * @apiName         ShowUser
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_show
     * @apiPermission   users_full_access
     *
     * @apiParam {Integer} id   User id
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiUse UserObject
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "id": 1,
     *    "full_name": "Admin",
     *    "email": "admin@example.com",
     *    "url": "",
     *    "company_id": 1,
     *    "payroll_access": 1,
     *    "billing_access": 1,
     *    "avatar": "",
     *    "screenshots_active": 1,
     *    "manual_time": 0,
     *    "permanent_tasks": 0,
     *    "computer_time_popup": 300,
     *    "poor_time_popup": "",
     *    "blur_screenshots": 0,
     *    "role": { "id": 2, "name": "user", "deleted_at": null,
     *              "created_at": "2018-10-12 11:44:08", "updated_at": "2018-10-12 11:44:08" },
     *    "web_and_app_monitoring": 1,
     *    "webcam_shots": 0,
     *    "screenshots_interval": 9,
     *    "active": 1,
     *    "deleted_at": null,
     *    "created_at": "2018-10-18 09:36:22",
     *    "updated_at": "2018-10-18 09:36:22",
     *    "role_id": 1,
     *    "timezone": null,
     *  }
     *
     * @apiUse         400Error
     * @apiUse         UnauthorizedError
     * @apiUse         ItemNotFoundError
     * @apiUse         ForbiddenError
     * @apiUse         ValidationError
     */

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws Exception
     */
    public function show(Request $request): JsonResponse
    {
        Filter::listen($this->getEventUniqueName('answer.success.item.show'), static function ($item) {
            $sortedRoles = [];
            foreach ($item->projectsRelation as $projectRole) {
                if (!array_key_exists($projectRole->role_id, $sortedRoles)) {
                    $sortedRoles[$projectRole->role_id] = [
                        'role' => $item->role,
                        'user_id' => $item->id,
                        'project_ids' => []
                    ];
                }

                $sortedRoles[$projectRole->role_id]['project_ids'][] = $projectRole->project_id;
            }

            $item->project_roles = $sortedRoles;
            unset($item->projectsRelation);
            return $item;
        });
        return parent::show($request);
    }

    /**
     * @api             {post} /v1/users/remove Destroy
     * @apiDescription  Destroy User
     *
     * @apiVersion      1.0.0
     * @apiName         DestroyUser
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_remove
     * @apiPermission   users_full_access
     *
     * @apiParam {Integer}  id  ID of the target user
     *
     * @apiParamExample {json} Request Example
     * {
     *   "id": 1
     * }
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   message  Destroy status
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "message": "Item has been removed"
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ValidationError
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */

    /**
     * @apiDeprecated   since 1.0.0
     * @api             {post} /v1/users/bulk-edit Bulk Edit
     * @apiDescription  Editing Multiple Users
     *
     * @apiVersion      1.0.0
     * @apiName         bulkEditUsers
     * @apiGroup        User
     *
     * @apiPermission   users_bulk_edit
     * @apiPermission   users_full_access
     */

    /**
     * @api             {get,post} /v1/users/count Count
     * @apiDescription  Count Users
     *
     * @apiVersion      1.0.0
     * @apiName         Count
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiPermission   users_count
     * @apiPermission   users_full_access
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     * @apiSuccess {String}   total    Amount of users that we have
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *    "total": 2
     *  }
     *
     * @apiUse          400Error
     * @apiUse          ForbiddenError
     * @apiUse          UnauthorizedError
     */

    /**
     * @apiDeprecated   since 1.0.0 use now (#Project_Users:List)
     * @api             {post} /v1/users/relations Relations
     * @apiDescription  Show attached users and to whom the user is attached
     *
     * @apiVersion      1.0.0
     * @apiName         RelationsUser
     * @apiGroup        User
     *
     * @apiPermission   users_relations
     */

    /**
     * TODO: apidoc
     *
     * @param SendInviteUserRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function sendInvite(SendInviteUserRequest $request)
    {
        $requestData = $request->validated();

        $itemsQuery = $this->applyQueryFilter($this->getQuery(), ['id' => $requestData['id']]);
        $item = $itemsQuery->first();
        if (!$item) {
            return new JsonResponse(
                [
                    'success' => false,
                    'error_type' => 'query.item_not_found',
                    'message' => 'Item not found'
                ],
                404
            );
        }

        $password = Str::random(16);
        $item->password = $password;
        $item->invitation_sent = true;
        $item->save();

        Mail::to($item->email)->send(new UserCreated($item->email, $password));

        return new JsonResponse([
            'success' => true,
            'res' => $item,
        ]);
    }

    /**
     * @param User $user
     * @param $requestData array
     * @return User
     */
    public function saveRelations(User $user, array $requestData): User
    {
        $user->projectsRelation()->delete();
        $projectRoles = $requestData['project_roles'] ?? [];

        $relations = [];
        foreach ($projectRoles as $roleID => $data) {
            foreach ($data['project_ids'] as $projectID) {
                $relations[] = new ProjectsUsers([
                    'project_id' => $projectID,
                    'role_id' => $roleID,
                    'user_id' => $user->id,
                ]);
            }
        }

        $user->projectsRelation()->saveMany($relations);
        return $user;
    }

    /**
     * @api             {patch} /v1/users/activity Activity
     * @apiDescription  Updates the time of the user's last activity
     *
     * @apiVersion      1.0.0
     * @apiName         Activity
     * @apiGroup        User
     *
     * @apiUse          AuthHeader
     *
     * @apiSuccess {Boolean}  success  Indicates successful request when `TRUE`
     *
     * @apiSuccessExample {json} Response Example
     *  HTTP/1.1 200 OK
     *  {
     *    "success": true,
     *  }
     *
     * @apiUse          UnauthorizedError
     */
    public function updateActivity(): JsonResponse
    {
        $user = auth()->user();
        /* @var User $user */
        $user->update(['last_activity' => Carbon::now()]);

        return new JsonResponse(['success' => true]);
    }

    /**
     * Get Query Builder
     *
     * @param bool $withRelations
     * @param bool $withSoftDeleted
     * @return Builder
     */
    protected function getQuery($withRelations = true, $withSoftDeleted = false): Builder
    {
        /** @var User $user */
        $user = Auth::user();
        $userId = $user->id;
        $query = parent::getQuery($withRelations, $withSoftDeleted);
        $full_access = $user->allowed('users', 'full_access');
        $action_method = Route::getCurrentRoute()->getActionMethod();

        if ($full_access) {
            return $query;
        }

        $rules = self::getControllerRules();
        $rule = $rules[$action_method] ?? null;
        if (isset($rule)) {
            [$object, $action] = explode('.', $rule);
            // Check user default role
            if (Role::can($user, $object, $action)) {
                return $query;
            }

            $query->where(static function (Builder $query) use ($userId, $object, $action) {
                $roleSubquery = static function (Builder $query) use ($userId, $object, $action) {
                    $query->where('user_id', $userId)->whereHas(
                        'role',
                        static function (Builder $query) use ($object, $action) {
                            $query->whereHas('rules', static function (Builder $query) use ($object, $action) {
                                $query->where([
                                    'object' => $object,
                                    'action' => $action,
                                    'allow' => true,
                                ])->select('id');
                            })->select('id');
                        }
                    )->select('id');
                };

                // Filter by project roles of the user
                // Users assigned to the project
                $query->whereHas('projects.usersRelation', $roleSubquery);

                // Users assigned to tasks in the project
                $query->orWhereHas('tasks.project.usersRelation', $roleSubquery);

                /*
                // Users has tracked intervals in tasks of the project
                $query->orWhereHas('timeIntervals.task.project.usersRelation', $roleSubquery);
                */

                // For read and edit access include own user data
                $query->when($action !== 'remove', static function (Builder $query) use ($userId) {
                    $query->orWhere('id', $userId);
                });
            });
        }

        return $query;
    }
}
