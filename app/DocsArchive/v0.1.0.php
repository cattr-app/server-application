<?php
/**
 * @apiVersion 0.1.0
 * @apiName Login
 * @apiGroup Auth
 *
 * @apiParam {String}   login       User login
 * @apiParam {String}   password    User password
 * @apiParam {String}   recaptcha   Recaptcha token
 *
 * @apiSuccess {String}     access_token  Token
 * @apiSuccess {String}     token_type    Token Type
 * @apiSuccess {String}     expires_in    Token TTL in seconds
 * @apiSuccess {Array}      user          User Entity
 *
 * @apiError (Error 401) {String} Error Error
 *
 * @apiParamExample {json} Request Example
 *  {
 *      "login":      "johndoe@example.com",
 *      "password":   "amazingpassword",
 *      "recaptcha":  "03AOLTBLR5UtIoenazYWjaZ4AFZiv1OWegWV..."
 *  }
 *
 * @apiUse AuthAnswer
 * @apiUse UnauthorizedError
 */
/**
 * @api {any} /api/auth/logout Logout
 * @apiDescription Invalidate JWT
 * @apiVersion 0.1.0
 * @apiName Logout
 * @apiGroup Auth
 *
 * @apiSuccess {String}    message    Action result message
 *
 * @apiSuccessExample {json} Answer Example
 *  {
 *      "message": "Successfully logged out"
 *  }
 *
 * @apiUse UnauthorizedError
 */
/**
 * @api {any} /api/auth/logout Logout
 * @apiDescription Invalidate JWT
 * @apiVersion 0.1.0
 * @apiName Logout
 * @apiGroup Auth
 *
 * @apiParamExample {json} Request Example
 *  {
 *      "token": "eyJ0eXAiOiJKV1QiLCJhbGciO..."
 *  }
 *
 * @apiSuccess {String}    message    Action result message
 *
 * @apiSuccessExample {json} Answer Example
 *  {
 *      "message": "Successfully ended all sessions"
 *  }
 *
 * @apiUse UnauthorizedError
 */
/**
 * @api {get} /api/auth/me Me
 * @apiDescription Get authenticated User Entity
 *
 * @apiVersion 0.1.0
 * @apiName Me
 * @apiGroup Auth
 *
 * @apiSuccess {String}     access_token  Token
 * @apiSuccess {String}     token_type    Token Type
 * @apiSuccess {String}     expires_in    Token TTL in seconds
 * @apiSuccess {Array}      user          User Entity
 *
 * @apiUse UnauthorizedError
 *
 * @apiSuccessExample {json} Answer Example
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
 *   "web_and_app_monitoring": 1,
 *   "webcam_shots": 0,
 *   "screenshots_interval": 9,
 *   "active": "active",
 *   "deleted_at": null,
 *   "created_at": "2018-09-25 06:15:08",
 *   "updated_at": "2018-09-25 06:15:08",
 *   "timezone": null
 * }
 */
/**
 * @api {post} /api/auth/refresh Refresh
 * @apiDescription Refresh JWT
 *
 * @apiVersion 0.1.0
 * @apiName Refresh
 * @apiGroup Auth
 *
 * @apiUse UnauthorizedError
 *
 * @apiUse AuthAnswer
 */
/**
 * @api {post} /api/auth/send-reset Send reset e-mail
 * @apiDescription Get user JWT
 *
 *
 * @apiVersion 0.1.0
 * @apiName Send reset
 * @apiGroup Auth
 *
 * @apiParam {String}   login       User login
 * @apiParam {String}   recaptcha   Recaptcha token
 *
 * @apiError (Error 401) {String} Error Error
 *
 * @apiParamExample {json} Request Example
 *  {
 *      "login":      "johndoe@example.com",
 *      "recaptcha":  "03AOLTBLR5UtIoenazYWjaZ4AFZiv1OWegWV..."
 *  }
 *
 * @apiUse AuthAnswer
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/auth/reset Reset
 * @apiDescription Get user JWT
 *
 *
 * @apiVersion 0.1.0
 * @apiName Reset
 * @apiGroup Auth
 *
 * @apiParam {String}   login       User login
 * @apiParam {String}   token       Password reset token
 * @apiParam {String}   password    User password
 * @apiParam {String}   recaptcha   Recaptcha token
 *
 * @apiSuccess {String}     access_token  Token
 * @apiSuccess {String}     token_type    Token Type
 * @apiSuccess {String}     expires_in    Token TTL in seconds
 * @apiSuccess {Array}      user          User Entity
 *
 * @apiError (Error 401) {String} Error Error
 *
 * @apiParamExample {json} Request Example
 *  {
 *      "login":      "johndoe@example.com",
 *      "token":      "16184cf3b2510464a53c0e573c75740540fe...",
 *      "password":   "amazingpassword",
 *      "recaptcha":  "03AOLTBLR5UtIoenazYWjaZ4AFZiv1OWegWV..."
 *  }
 *
 * @apiUse AuthAnswer
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/register/create Create
 * @apiName CreateRegistration
 * @apiGroup Registration
 * @apiDescription Create unique register token and send email
 * @apiVersion 0.1.0
 *
 * @apiParam {String} email E-Mail
 *
 * @apiParamExample {json} Request Example
 * {
 *   "email": "test@example.com"
 * }
 *
 * @apiSuccess {String} key Unique registration token
 *
 * @apiSuccessExample {json} Response Example
 * {
 *   "key": "..."
 * }
 */
/**
 * @api {get} /api/auth/register/{key} Get
 * @apiName GetRegistration
 * @apiGroup Registration
 * @apiDescription Returns registration form data by a registration token
 * @apiVersion 0.1.0
 *
 * @apiSuccess {String} email Registration e-mail
 *
 * @apiSuccessExample {json} Response Example
 * {
 *   "email": "test@example.com"
 * }
 */
/**
 * @api {post} /api/v1/register/create Create
 * @apiName CreateRegistration
 * @apiGroup Registration
 * @apiDescription Create unique register token and send email
 * @apiVersion 0.1.0
 *
 * @apiParam {String} email E-Mail
 *
 * @apiParamExample {json} Request Example
 * {
 *   "email": "test@example.com"
 * }
 *
 * @apiSuccess {String} key Unique registration token
 *
 * @apiSuccessExample {json} Response Example
 * {
 *   "key": "..."
 * }
 */
/**
 * @api {get} /api/auth/register/{key} Get
 * @apiName GetRegistration
 * @apiGroup Registration
 * @apiDescription Returns registration form data by a registration token
 * @apiVersion 0.1.0
 *
 * @apiSuccess {String} email Registration e-mail
 *
 * @apiSuccessExample {json} Response Example
 * {
 *   "email": "test@example.com"
 * }
 */
/**
 * @api {get, post} /api/v1/projects/list List
 * @apiDescription Get list of Projects
 * @apiVersion 0.1.0
 * @apiName GetProjectList
 * @apiGroup Project
 *
 * @apiParam {Integer}  [id]          `QueryParam` Project id
 * @apiParam {Integer}  [user_id]     `QueryParam` Project User id
 * @apiParam {String}   [name]        `QueryParam` Project name
 * @apiParam {String}   [description] `QueryParam` Project description
 * @apiParam {String}   [created_at]  `QueryParam` Project date time of create
 * @apiParam {String}   [updated_at]  `QueryParam` Project date time of update
 *
 * @apiUse ProjectRelations
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id":          [">", 1]
 *      "user_id":     ["=", [1,2,3]],
 *      "name":        ["like", "%lorem%"],
 *      "description": ["like", "%lorem%"],
 *      "created_at":  [">", "2019-01-01 00:00:00"],
 *      "updated_at":  ["<", "2019-01-01 00:00:00"]
 *  }
 * @apiUse ProjectRelationsExample
 * @apiUse UnauthorizedError
 *
 * @apiSuccess {Object[]} ProjectList                     Projects
 * @apiSuccess {Object}   ProjectList.Project             Project
 * @apiSuccess {Integer}  ProjectList.Project.id          Project id
 * @apiSuccess {String}   ProjectList.Project.name        Project name
 * @apiSuccess {String}   ProjectList.Project.description Project description
 * @apiSuccess {String}   ProjectList.Project.created_at  Project date time of create
 * @apiSuccess {String}   ProjectList.Project.updated_at  Project date time of update
 * @apiSuccess {String}   ProjectList.Project.deleted_at  Project date time of delete
 * @apiSuccess {Object[]} ProjectList.Project.users       Project Users
 * @apiSuccess {Object[]} ProjectList.Project.tasks       Project Tasks
 *
 * @apiSuccessExample {json} Answer Example
 * [
 *   {
 *     "id": 1,
 *     "company_id": 0,
 *     "name": "Eos est amet sunt ut autem harum.",
 *     "description": "Dolores rem et sed beatae...",
 *     "deleted_at": null,
 *     "created_at": "2018-09-25 06:15:08",
 *     "updated_at": "2018-09-25 06:15:08"
 *   },
 *   {
 *     "id": 2,
 *     "company_id": 1,
 *     "name": "Incidunt officiis.",
 *     "description": "Quas quam sint vero...",
 *     "deleted_at": null,
 *     "created_at": "2018-09-25 06:15:11",
 *     "updated_at": "2018-09-25 06:15:11"
 *   }
 * ]
 */
/**
 * @api {post} /api/v1/projects/create Create
 * @apiDescription Create Project
 * @apiVersion 0.1.0
 * @apiName CreateProject
 * @apiGroup Project
 *
 * @apiParam {String}  name         Project name
 * @apiParam {String}  description  Project description
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "name": "SampleOriginalProjectName",
 *      "description": "Code-monkey development group presents"
 *  }
 *
 * @apiSuccess {Object}   res             Response
 * @apiSuccess {Integer}  res.id          Project id
 * @apiSuccess {String}   res.name        Project name
 * @apiSuccess {String}   res.description Project description
 * @apiSuccess {String}   res.created_at  Project date time of create
 * @apiSuccess {String}   res.updated_at  Project date time of update
 *
 * @apiUse DefaultCreateErrorResponse
 * @apiUse UnauthorizedError
 *
 * @apiSuccessExample {json} Answer Example
 * {
 *   "res": {
 *     "name": "SampleOriginalProjectName",
 *     "description": "Code-monkey development group presents",
 *     "updated_at": "2018-09-27 04:55:29",
 *     "created_at": "2018-09-27 04:55:29",
 *     "id": 6
 *   }
 * }
 */
/**
 * @api {get, post} /api/v1/projects/show Show
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id":          1,
 *      "user_id":     ["=", [1,2,3]],
 *      "name":        ["like", "%lorem%"],
 *      "description": ["like", "%lorem%"],
 *      "created_at":  [">", "2019-01-01 00:00:00"],
 *      "updated_at":  ["<", "2019-01-01 00:00:00"]
 *  }
 * @apiUse ProjectRelationsExample
 * @apiDescription Show Project
 * @apiVersion 0.1.0
 * @apiName ShowProject
 * @apiGroup Project
 *
 * @apiParam {Integer}  id            `QueryParam` Project id
 * @apiParam {Integer}  [user_id]     `QueryParam` Project User id
 * @apiParam {String}   [name]        `QueryParam` Project name
 * @apiParam {String}   [description] `QueryParam` Project description
 * @apiParam {String}   [created_at]  `QueryParam` Project date time of create
 * @apiParam {String}   [updated_at]  `QueryParam` Project date time of update
 * @apiUse ProjectRelations
 *
 * @apiSuccess {Object}   Project             Project object
 * @apiSuccess {Integer}  Project.id          Project id
 * @apiSuccess {String}   Project.name        Project name
 * @apiSuccess {String}   Project.description Project description
 * @apiSuccess {String}   Project.created_at  Project date time of create
 * @apiSuccess {String}   Project.updated_at  Project date time of update
 * @apiSuccess {String}   Project.deleted_at  Project date time of delete
 * @apiSuccess {Object[]} Project.users       Project Users
 * @apiSuccess {Object[]} Project.tasks       Project Tasks
 *
 * @apiSuccessExample {json} Answer Example
 * {
 *   "id": 1,
 *   "company_id": 0,
 *   "name": "Eos est amet sunt ut autem harum.",
 *   "description": "Dolores rem et sed beatae architecto...",
 *   "deleted_at": null,
 *   "created_at": "2018-09-25 06:15:08",
 *   "updated_at": "2018-09-25 06:15:08"
 * }
 *
 * @apiSuccessExample {json} Answer Relation Example
 * {
 *   "id": 1,
 *   "company_id": 0,
 *   "name": "Eos est amet sunt ut autem harum.",
 *   "description": "Dolores rem et sed beatae architecto assumenda illum reprehenderit...",
 *   "deleted_at": null,
 *   "created_at": "2018-09-25 06:15:08",
 *   "updated_at": "2018-09-25 06:15:08",
 *   "tasks": [
 *   {
 *   "id": 1,
 *   "project_id": 1,
 *   "task_name": "Enim et sit similique.",
 *   "description": "Adipisci eius qui quia et rerum rem perspiciatis...",
 *   "active": 1,
 *   "user_id": 1,
 *   "assigned_by": 1,
 *   "url": null,
 *   "created_at": "2018-09-25 06:15:08",
 *   "updated_at": "2018-09-25 06:15:08",
 *   "deleted_at": null,
 *   "time_intervals": [
 *     {
 *       "id": 1,
 *       "task_id": 1,
 *       "start_at": "2006-05-31 16:15:09",
 *       "end_at": "2006-05-31 16:20:07",
 *       "created_at": "2018-09-25 06:15:08",
 *       "updated_at": "2018-09-25 06:15:08",
 *       "deleted_at": null,
 *       "count_mouse": 88,
 *       "count_keyboard": 127,
 *       "user_id": 1
 *     },
 *     {
 *       "id": 2,
 *       "task_id": 1,
 *       "start_at": "2006-05-31 16:20:08",
 *       "end_at": "2006-05-31 16:25:06",
 *       "created_at": "2018-09-25 06:15:08",
 *       "updated_at": "2018-09-25 06:15:08",
 *       "deleted_at": null,
 *       "count_mouse": 117,
 *       "count_keyboard": 23,
 *       "user_id": 1
 *     }
 *   ]
 * }
 *
 * @apiUse DefaultShowErrorResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {put, post} /api/v1/projects/edit Edit
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id": 1,
 *      "name": "test",
 *      "description": "test"
 *  }
 *
 * @apiDescription Edit Project
 * @apiVersion 0.1.0
 * @apiName EditProject
 * @apiGroup Project
 *
 * @apiParam {String}  id           Project id
 * @apiParam {String}  name         Project name
 * @apiParam {String}  description  Project description
 *
 * @apiSuccess {Object}   res             Response object
 * @apiSuccess {Integer}  res.id          Project id
 * @apiSuccess {String}   res.name        Project name
 * @apiSuccess {String}   res.description Project description
 * @apiSuccess {String}   res.created_at  Project date time of create
 * @apiSuccess {String}   res.updated_at  Project date time of update
 * @apiSuccess {String}   res.deleted_at  Project date time of delete
 *
 * @apiUse DefaultEditErrorResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {delete, post} /api/v1/projects/remove Destroy
 * @apiUse DefaultDestroyRequestExample
 * @apiDescription Destroy Project
 * @apiVersion 0.1.0
 * @apiName DestroyProject
 * @apiGroup Project
 *
 * @apiParam {String} id Project id
 *
 * @apiUse DefaultDestroyResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {get} /api/v1/projects-roles/list List
 * @apiDescription Get list of Projects Roles relations
 * @apiVersion 0.1.0
 * @apiName GetProjectRolesList
 * @apiGroup ProjectRoles
 *
 * @apiParam {Integer} [project_id] `QueryParam` Project ID
 * @apiParam {Integer} [role_id]    `QueryParam` Role ID
 *
 * @apiSuccess {Object[]} ProjectRolesList ProjectRoles
 *
 * @apiUse UnauthorizedError
 *
 *
 * @apiSuccessExample {json} Response example
 * {
 *   [
 *     {
 *       "project_id": 1,
 *       "role_id": 1,
 *       "created_at": "2018-10-25 08:41:35",
 *       "updated_at": "2018-10-25 08:41:35"
 *     }
 *   ]
 * }
 *
 * @apiParamExample {json} Request example
 * {
 *   "project_id": 1,
 *   "role_id": 1
 * }
 */
/**
 * @api {post} /api/v1/projects-roles/create Create
 * @apiDescription Create Project Roles relation
 *
 * @apiVersion 0.1.0
 *
 * @apiName CreateProjectRoles
 * @apiGroup ProjectRoles
 *
 * @apiUse DefaultBulkCreateErrorResponse
 * @apiUse UnauthorizedError
 *
 *
 * @apiErrorExample {json} Error example
 * {
 *   "error": "Validation fail",
 *     "reason": {
 *       "project_id": [
 *         "The selected project id is invalid."
 *     ],
 *     "role_id": [
 *       "The selected role id is invalid."
 *     ]
 *   }
 * }
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "project_id": 1,
 *      "role_id": 1
 *  }
 *
 * @apiSuccessExample {json} Simple Response Example
 * [
 *   {
 *     "project_id": 1,
 *     "role_id": 1,
 *     "updated_at": "2018-10-17 08:28:18",
 *     "created_at": "2018-10-17 08:28:18",
 *     "id": 0
 *   }
 * ]
 *
 * @apiErrorExample {json} Error Example
 * {
 *   "error": "Validation fail",
 *   "reason": {
 *     "project_id": [
 *       "The selected project id is invalid."
 *     ],
 *     "role_id": [
 *       "The selected role id is invalid."
 *     ]
 *   }
 * }
 */
/**
 * @api {post} /api/v1/projects-roles/bulk-create Bulk Create
 * @apiDescription Multiple Create Project Roles relation
 * @apiVersion 0.1.0
 * @apiName BulkCreateProjectRoles
 * @apiGroup ProjectRoles
 *
 * @apiParamExample {json} Request example
 * {
 *   "relations": [
 *     {
 *       "project_id": 1,
 *       "role_id": 1
 *     }
 *   ]
 * }
 *
 * @apiSuccessExample {json} Response example
 * {
 *
 * }
 *
 * @apiSuccess {Object[]}  messages                        Project Roles messages
 * @apiSuccess {Object}    messages.object                 Project Role
 * @apiSuccess {Integer}   messages.object.project_id      Project id
 * @apiSuccess {Integer}   messages.object.role_id         Project Role id
 * @apiSuccess {String}    messages.object.updated_at      Project Role last update datetime
 * @apiSuccess {String}    messages.object.created_at      Project Role creation datetime
 *
 * @apiErrorExample {json} Error response example
 * {
 *   "messages": [
 *     {
 *       "error": "Validation fail",
 *       "reason": {
 *         "project_id": [
 *           "The selected project id is invalid."
 *         ],
 *         "role_id": [
 *           "The selected role id is invalid."
 *         ]
 *     },
 *     "code": 400
 *   }
 *   ]
 * }
 *
 * @apiParam   {Object[]}  array                   Project Roles
 * @apiParam   {Object}    array.object            ProjectRole
 * @apiParam   {Integer}   array.object.project_id Project id
 * @apiParam   {Integer}   array.object.role_id    Role id
 *
 *
 * @apiUse UnauthorizedError

 */
/**
 * @api {remove, post} /api/v1/projects-roles/remove Destroy
 * @apiDescription Destroy Project Roles relation
 * @apiVersion 0.1.0
 * @apiName DestroyProjectRoles
 * @apiGroup ProjectRoles
 *
 * @apiParam      {Object}   object               `QueryParam`
 * @apiParam      {Integer}  object.project_id    `QueryParam`
 * @apiParam      {Integer}  object.role_id       `QueryParam`
 *
 * @apiParamExample {json} Request example
 * {
 *    "project_id": 1,
 *    "role_id": 1
 * }
 *
 * @apiSuccess    {Object}   object           message
 * @apiSuccess    {String}   object.message   body
 *
 * @apiSuccessExample {json} Response example
 * {
 *    "message": "Item has been removed"
 * }
 *
 * @apiUse DefaultDestroyRequestExample
 * @apiUse DefaultBulkDestroyErrorResponse
 * @apiUse DefaultDestroyResponse
 *
 * @apiUse UnauthorizedError
 *
 *
 * @apiErrorExample (403) {json} Not allowed action example
 * {
 *   "error": "Access denied to projects-roles/remove",
 *   "reason": "action is not allowed"
 * }
 *
 * @apiErrorExample (404) {json} Not found example
 * {
 *   "error": "No query results for model [App\\User]."
 * }
 *
 * @apiError {String} error  Error
 * @apiError {String} reason Reason
 *
 * @apiErrorExample (400) {json} Validation fail example
 * {
 *   "error": "Validation fail",
 *   "reason": {
 *     "project_id": [
 *       "The selected project id is invalid."
 *     ],
 *     "role_id": [
 *       "The selected role id is invalid."
 *     ]
 *   }
 * }
 */
/**
 * @api {post} /api/v1/projects-roles/bulk-remove Bulk Destroy
 * @apiDescription Multiple Destroy Project Roles relation
 * @apiVersion 0.1.0
 * @apiName BulkDestroyProjectRoles
 * @apiGroup ProjectRoles
 *
 * @apiParam   {Object[]}  array                   ProjectRoles
 * @apiParam   {Object}    array.object            Project Role relation
 * @apiParam   {Integer}   array.object.project_id Project id
 * @apiParam   {Integer}   array.object.role_id    Role id
 *
 * @apiSuccess {Object[]}  array                   Messages
 * @apiSuccess {Object}    array.object            Message
 */
/**
 * @api {any} /api/v1/projects-users/list List
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "user_id":        ["=", [1,2,3]],
 *      "project_id":     [">", 1]
 *  }
 * @apiUse ProjectUserRelationsExample
 * @apiDescription Get list of Projects Users relations
 * @apiVersion 0.1.0
 * @apiName List
 * @apiGroup Project Users
 *
 * @apiParam {Integer} [project_id] `QueryParam` Project-User Project id
 * @apiParam {Integer} [user_id]    `QueryParam` Project-User User id
 * @apiUse ProjectUserRelations
 *
 * @apiSuccess {Object[]}  ProjectUsersList                          Project-Users
 * @apiSuccess {Object}    ProjectUsersList.ProjectUser             Project-User
 * @apiSuccess {Integer}   ProjectUsersList.ProjectUser.user_id     Project-User User id
 * @apiSuccess {Integer}   ProjectUsersList.ProjectUser.project_id  Project-User Project id
 * @apiSuccess {String}    ProjectUsersList.ProjectUser.created_at  Project-User date time of create
 * @apiSuccess {String}    ProjectUsersList.ProjectUser.updated_at  Project-User date time of update
 * @apiSuccess {Object}    ProjectUsersList.ProjectUser.user        Project-User User
 * @apiSuccess {Object}    ProjectUsersList.ProjectUser.project     Project-User Project
 *
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/projects-users/create Create
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "project_id": 1,
 *      "user_id": 45
 *  }
 * @apiDescription Create Project Users relation
 * @apiVersion 0.1.0
 * @apiName Create
 * @apiGroup Project Users
 *
 * @apiParam   {Integer}   project_id              Project-User Project id
 * @apiParam   {Integer}   user_id                 Project-User User id
 *
 * @apiSuccess {Integer}   array.object.user_id     Project-User User id
 * @apiSuccess {Integer}   array.object.project_id  Project-User Project id
 * @apiSuccess {String}    array.object.created_at  Project-User date time of create
 * @apiSuccess {String}    array.object.updated_at  Project-User date time of update
 *
 * @apiUse DefaultCreateErrorResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/projects-users/bulk-create Bulk Create
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "relations":
 *      [
 *          {
 *              "project_id":1,
 *              "user_id":3
 *          },
 *          {
 *              "project_id":1,
 *              "user_id":2
 *          }
 *      ]
 *  }
 *
 * @apiDescription Multiple Create Project Users relation
 * @apiVersion 0.1.0
 * @apiName Bulk Create
 * @apiGroup Project Users
 *
 * @apiParam {Object[]} relations                   Project-User relations
 * @apiParam {Object}   relations.object            Object Project-User relation
 * @apiParam {Integer}  relations.object.project_id Project-User Project id
 * @apiParam {Integer}  relations.object.user_id    Project-User User id
 *
 * @apiSuccess {Object[]} messages                   Project-Users
 * @apiSuccess {Object}   messages.object            Project-User
 * @apiSuccess {Integer}  messages.object.user_id    Project-User User id
 * @apiSuccess {Integer}  messages.object.project_id Project-User Project id
 * @apiSuccess {String}   messages.object.created_at Project-User date time of create
 * @apiSuccess {String}   messages.object.updated_at Project-User date time of update
 *
 * @apiSuccessExample {json} Simple Response Example
 * {
 *   "messages": [
 *     {
 *       "project_id": 1,
 *       "user_id": 3,
 *       "updated_at": "2018-10-17 03:58:05",
 *       "created_at": "2018-10-17 03:58:05",
 *       "id": 0
 *     },
 *     {
 *       "project_id": 1,
 *       "user_id": 2,
 *       "created_at": "2018-10-17 03:58:05",
 *       "updated_at": "2018-10-17 03:58:05"
 *     }
 *   ]
 * }
 *
 * @apiUse DefaultBulkCreateErrorResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {delete, post} /api/v1/projects-users/remove Destroy
 * @apiDescription Destroy Project Users relation
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "project_id":1,
 *      "user_id":4
 *  }
 * @apiVersion 0.1.0
 * @apiName Destroy
 * @apiGroup Project Users
 *
 * @apiParam             {Integer} project_id           Project-User Project id
 * @apiParam             {Integer} user_id              Project-User User id
 *
 * @apiSuccess {String} message Message about success item remove
 *
 * @apiSuccessExample {json} Simple Response Example
 * {
 *    "message": "Item has been removed"
 * }
 *
 * @apiError (Error 400) {String} error     Name of error
 * @apiError (Error 400) {String} reason    Reason of error
 *
 * @apiUse UnauthorizedError
 *
 * @apiErrorExample {json} Simple Error Example
 * {
 *   "error": "Item has not been removed",
 *   "reason": "Item not found"
 * }
 */
/**
 * @api {post} /api/v1/projects-users/bulk-remove Bulk Destroy
 * @apiParamExample {json} Simple Request Example
 * {
 *  "relations":
 *  [
 *      {
 *          "project_id": 1,
 *          "user_id": 4
 *      },
 *      {
 *          "project_id": 2,
 *          "user_id": 4
 *      }
 *  ]
 * }
 * @apiDescription Multiple Destroy Project Users relation
 * @apiVersion 0.1.0
 * @apiName Bulk Destroy
 * @apiGroup Project Users
 *
 * @apiParam    {Object[]} relations                    Project-User relations
 * @apiParam    {Object}   relations.object             Object Project-User relation
 * @apiParam    {Integer}  relations.object.project_id  Project-User Project id
 * @apiParam    {Integer}  relations.object.user_id     Project-User User id
 *
 * @apiSuccess  {Object[]} messages                     Messages
 * @apiSuccess  {Object}   messages.object Item removal Message status
 *
 * @apiUse DefaultBulkDestroyErrorResponse
 */
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
 */

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
 * @api {post} /api/v1/users/bulk-edit Bulk Edit
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
 */
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
 */
/**
 * @api            {post} /api/v1/time-intervals/create Create
 * @apiDescription Create Time Interval
 * @apiVersion     0.1.0
 * @apiName        Create
 * @apiGroup       Time Interval
 *
 * @apiUse         UnauthorizedError
 *
 * @apiParamExample {json} Request Example
 * {
 *   "task_id": 1,
 *   "user_id": 1,
 *   "start_at": "2013-04-12T16:40:00-04:00",
 *   "end_at": "2013-04-12T16:40:00-04:00"
 * }
 *
 * @apiSuccessExample {json} Answer Example
 * {
 *   "interval": {
 *     "id": 2251,
 *     "task_id": 1,
 *     "start_at": "2013-04-12 20:40:00",
 *     "end_at": "2013-04-12 20:40:00",
 *     "created_at": "2018-10-01 03:20:59",
 *     "updated_at": "2018-10-01 03:20:59",
 *     "count_mouse": 0,
 *     "count_keyboard": 0,
 *     "user_id": 1
 *   }
 * }
 *
 * @apiParam {Integer}  task_id   Task id
 * @apiParam {Integer}  user_id   User id
 * @apiParam {String}   start_at  Interval time start
 * @apiParam {String}   end_at    Interval time end
 *
 * @apiParam {Integer}  [count_mouse]     Mouse events count
 * @apiParam {Integer}  [count_keyboard]  Keyboard events count
 *
 * @apiUse         WrongDateTimeFormatStartEndAt
 */
/**
 * @api            {post} /api/v1/time-intervals/bulk-create Bulk Create
 * @apiDescription Create Time Intervals
 * @apiVersion     0.1.0
 * @apiName        Bulk Create
 * @apiGroup       Time Interval
 *
 * @apiParam {String}   intervals           Serialized array of time intervals
 * @apiParam {Integer}  intervals.task_id   Task id
 * @apiParam {Integer}  intervals.user_id   User id
 * @apiParam {String}   intervals.start_at  Interval time start
 * @apiParam {String}   intervals.end_at    Interval time end
 * @apiParam {Binary}   screenshots[index]  Screenshot file
 *
 * @apiSuccess {Object[]} messages                 Messages
 * @apiSuccess {Object}   messages.id              TimeInterval id
 * @apiSuccess {Object}   messages.user_id.        User id
 * @apiSuccess {Object}   messages.start_at        Start datetime
 * @apiSuccess {Object}   messages.end_at          End datetime
 * @apiSuccess {Object}   messages.created_at      TimeInterval
 * @apiSuccess {Object}   messages.deleted_at      TimeInterval
 *
 * @apiError (400)  {Object[]} messages         Messages
 * @apiError (400)  {String}   messages.error   Error title
 * @apiError (400)  {String}   messages.reason  Error reason
 * @apiError (400)  {String}   messages.code    Error code
 *
 * @apiUse         UnauthorizedError
 * @apiUse         WrongDateTimeFormatStartEndAt
 */
/**
 * @api            {post} /api/v1/time-intervals/list List
 * @apiDescription Get list of Time Intervals
 * @apiVersion     0.1.0
 * @apiName        List
 * @apiGroup       Time Interval
 *
 * @apiParam {Integer}  [id]         `QueryParam` Time Interval id
 * @apiParam {Integer}  [task_id]    `QueryParam` Time Interval Task id
 * @apiParam {Integer}  [user_id]    `QueryParam` Time Interval User id
 * @apiParam {String}   [start_at]   `QueryParam` Interval Start DataTime
 * @apiParam {String}   [end_at]     `QueryParam` Interval End DataTime
 * @apiParam {String}   [created_at] `QueryParam` Time Interval Creation DateTime
 * @apiParam {String}   [updated_at] `QueryParam` Last Time Interval data update DataTime
 * @apiParam {String}   [deleted_at] `QueryParam` When Time Interval was deleted (null if not)
 *
 * @apiSuccess (200) {Object[]} TimeIntervalList Time Intervals
 *
 * @apiSuccessExample {json} Answer Example:
 * {
 *      {
 *          "id":1,
 *          "task_id":1,
 *          "start_at":"2006-06-20 15:54:40",
 *          "end_at":"2006-06-20 15:59:38",
 *          "created_at":"2018-10-15 05:54:39",
 *          "updated_at":"2018-10-15 05:54:39",
 *          "deleted_at":null,
 *          "count_mouse":42,
 *          "count_keyboard":43,
 *          "user_id":1
 *      },
 *      ...
 * }
 *
 * @apiUse         UnauthorizedError
 */

/**
 * @api            {post} /api/v1/time-intervals/show Show
 * @apiDescription Show Time Interval
 * @apiVersion     0.1.0
 * @apiName        Show
 * @apiGroup       Time Interval
 *
 * @apiParam {Integer}  id     Time Interval id
 *
 * @apiParamExample {json} Request Example
 * {
 *   "id": 1
 * }
 *
 * @apiSuccess {Object}  object TimeInterval
 * @apiSuccess {Integer} object.id
 *
 * @apiSuccessExample {json} Answer Example
 * {
 *   "id": 1,
 *   "task_id": 1,
 *   "start_at": "2006-05-31 16:15:09",
 *   "end_at": "2006-05-31 16:20:07",
 *   "created_at": "2018-09-25 06:15:08",
 *   "updated_at": "2018-09-25 06:15:08",
 *   "deleted_at": null,
 *   "count_mouse": 88,
 *   "count_keyboard": 127,
 *   "user_id": 1
 * }
 *
 * @apiUse         UnauthorizedError
 */
/**
 * @api            {post} /api/v1/time-intervals/edit Edit
 * @apiDescription Edit Time Interval
 * @apiVersion     0.1.0
 * @apiName        Edit
 * @apiGroup       Time Interval
 *
 * @apiParam {Integer}  id           Time Interval id
 * @apiParam {Integer}  [user_id]    Time Interval User id
 * @apiParam {String}   [start_at]   Interval Start DataTime
 * @apiParam {String}   [end_at]     Interval End DataTime
 * @apiParam {String}   [created_at] Time Interval Creation DateTime
 * @apiParam {String}   [updated_at] Last Time Interval data update DataTime
 * @apiParam {String}   [deleted_at] When Time Interval was deleted (null if not)
 *
 * @apiSuccess {Object} res                 TimeInterval
 * @apiSuccess {Object} res.id              TimeInterval id
 * @apiSuccess {Object} res.user_id.        User id
 * @apiSuccess {Object} res.start_at        Start datetime
 * @apiSuccess {Object} res.end_at          End datetime
 * @apiSuccess {Object} res.created_at      TimeInterval
 * @apiSuccess {Object} res.deleted_at      TimeInterval
 *
 *
 * @apiSuccessExample {json} Answer example
 * {
 * "res":
 *   {
 *     "id":1,
 *     "task_id":1,
 *     "start_at":"2018-10-03 10:00:00",
 *     "end_at":"2018-10-03 10:00:00",
 *     "created_at":"2018-10-15 05:50:39",
 *     "updated_at":"2018-10-15 05:50:43",
 *     "deleted_at":null,
 *     "count_mouse":42,
 *     "count_keyboard":43,
 *     "user_id":1
 *   }
 * }
 *
 *
 * @apiUse         UnauthorizedError
 */
/**
 * @api            {delete, post} /api/v1/time-intervals/remove Destroy
 * @apiDescription Destroy Time Interval
 * @apiVersion     0.1.0
 * @apiName        Destroy
 * @apiGroup       Time Interval
 *
 * @apiParam {Integer}   id Time interval id
 *
 * @apiSuccess {String} message Message
 *
 * @apiSuccessExample {json} Answer Example
 * {
 *   "message":"Item has been removed"
 * }
 *
 * @apiUse         UnauthorizedError
 */
/**
 * @api            {delete, post} /api/v1/time-intervals/bulk-remove Bulk Destroy
 * @apiDescription Multiple Destroy TimeInterval
 * @apiVersion     0.1.0
 * @apiName        Bulk Destroy
 * @apiGroup       Time Interval
 *
 * @apiParam {Object[]}    array              Time Intervals
 * @apiParam {Object}      array.object       Time Interval
 * @apiParam {Integer}     array.object.id    Time Interval id
 *
 * @apiParamExample {json} Request Example
 * {
 *   "intervals": [
 *     {
 *       "id": "1"
 *     }
 *   ]
 * }
 *
 * @apiSuccess {Object[]} messages               Messages
 * @apiSuccess {Object}   message                Message
 * @apiSuccess {String}   message.message        Status
 *
 * @apiSuccessExample {json} Response Example
 * {
 *   "messages": [
 *     {
 *       "message": "Item has been removed"
 *     }
 *   ]
 * }
 *
 * @apiError (404)  {Object[]} messages                 Messages
 * @apiError (404)  {Object}   messages.message         Message
 * @apiError (404)  {String}   messages.message.error   Error title
 * @apiError (404)  {String}   messages.message.reason  Error reason
 *
 * @apiErrorExample (404) {json} Errors Response Example
 * {
 *   "messages": [
 *     {
 *       "error": "Item has not been removed",
 *       "reason": "Item not found"
 *     }
 *   ]
 * }
 *
 * @apiUse         UnauthorizedError
 */
/**
 * @api {POST|GET} /api/v1/time/total Total
 * @apiParamExample {json} Request Example
 *  {
 *      "user_id":        1,
 *      "task_id":        ["=", [1,2,3]],
 *      "project_id":     [">", 1],
 *      "start_at":       "2005-01-01 00:00:00",
 *      "end_at":         "2019-01-01 00:00:00",
 *      "count_mouse":    [">=", 30],
 *      "count_keyboard": ["<=", 200],
 *      "id":             [">", 1]
 *  }
 * @apiUse RelationsExample
 * @apiDescription Get total of Time
 * @apiVersion 0.1.0
 * @apiName Total
 * @apiGroup Time
 *
 * @apiParam {Integer[]} [tasks_id]       `QueryParam` TimeInterval Task id
 * @apiParam {Integer}   [project_id]     `QueryParam` TimeInterval Task Project id
 * @apiParam {Integer}   [user_id]        `QueryParam` TimeInterval Task User ID
 * @apiParam {String}    [start_at]                    TimeInterval Start DataTime
 * @apiParam {String}    [end_at]                      TimeInterval End DataTime
 * @apiParam {Integer}   [count_mouse]    `QueryParam` TimeInterval Count mouse
 * @apiParam {Integer}   [count_keyboard] `QueryParam` TimeInterval Count keyboard
 * @apiParam {Integer}   [id]             `QueryParam` TimeInterval id
 * @apiUse Relations
 *
 * @apiSuccess {String}   current_datetime Current datetime of server
 * @apiSuccess {Integer}  time             Total time in seconds
 * @apiSuccess {String}   start            Datetime of first Time Interval start_at
 * @apiSuccess {String}   end              DateTime of last Time Interval end_at
 */
/**
 * @api {POST|GET} /api/v1/time/project Project
 * @apiParamExample {json} Request-Example:
 *  {
 *      "user_id":        1,
 *      "task_id":        ["=", [1,2,3]],
 *      "project_id":     ["<", 2],
 *      "start_at":       "2005-01-01 00:00:00",
 *      "end_at":         "2019-01-01 00:00:00",
 *      "count_mouse":    [">=", 30],
 *      "count_keyboard": ["<=", 200],
 *      "id":             [">", 1]
 *  }
 * @apiUse RelationsExample
 * @apiDescription Get time of project
 * @apiVersion 0.1.0
 * @apiName Project
 * @apiGroup Time
 *
 * @apiParam {Integer[]} [tasks_id]       `QueryParam` TimeInterval Task id
 * @apiParam {Integer}   project_id       `QueryParam` TimeInterval Task Project id
 * @apiParam {Integer}   [user_id]        `QueryParam` TimeInterval Task User id
 * @apiParam {String}    [start_at]                    TimeInterval Start DataTime
 * @apiParam {String}    [end_at]                      TimeInterval End DataTime
 * @apiParam {Integer}   [count_mouse]    `QueryParam` TimeInterval Count mouse
 * @apiParam {Integer}   [count_keyboard] `QueryParam` TimeInterval Count keyboard
 * @apiParam {Integer}   [id]             `QueryParam` TimeInterval id
 * @apiUse Relations
 *
 * @apiSuccess {String}   current_datetime Current datetime of server
 * @apiSuccess {Integer}  time             Total time of project in seconds
 * @apiSuccess {String}   start            Datetime of first Time Interval's start_at
 * @apiSuccess {String}   end              DateTime of last Time Interval's end_at
 *
 * @apiError (Error 400) {String} error  Name of error
 * @apiError (Error 400) {String} reason Reason of error
 */
/**
 * @api {POST|GET} /api/v1/time/tasks Tasks
 * @apiParamExample {json} Request-Example:
 *  {
 *      "user_id":        1,
 *      "task_id":        1 OR [1, 2, n] (multiple choice can be only achieved with POST),
 *      "project_id":     2,
 *      "start_at":       "2005-01-01 00:00:00",
 *      "end_at":         "2019-01-01 00:00:00",
 *      "count_mouse":    [">=", 30],
 *      "count_keyboard": ["<=", 200],
 *      "id":             [">", 1]
 *  }
 * @apiUse RelationsExample
 * @apiDescription Get tasks and its total time
 * @apiVersion 0.1.0
 * @apiName Tasks
 * @apiGroup Time
 *
 * @apiParam {Integer[]} [tasks_id]       `QueryParam` TimeInterval Task id
 * @apiParam {Integer}   [project_id]     `QueryParam` TimeInterval Task Project id
 * @apiParam {Integer}   [user_id]        `QueryParam` TimeInterval Task User id
 * @apiParam {String}    [start_at]                    TimeInterval Start DataTime
 * @apiParam {String}    [end_at]                      TimeInterval End DataTime
 * @apiParam {Integer}   [count_mouse]    `QueryParam` TimeInterval Count mouse
 * @apiParam {Integer}   [count_keyboard] `QueryParam` TimeInterval Count keyboard
 * @apiParam {Integer}   [id]             `QueryParam` TimeInterval ID
 * @apiUse Relations
 *
 * @apiSuccess {String}   current_datetime Current datetime of server
 * @apiSuccess {Object[]} tasks            Array of objects Task
 * @apiSuccess {Integer}  tasks.id         Tasks id
 * @apiSuccess {Integer}  tasks.user_id    Tasks User id
 * @apiSuccess {Integer}  tasks.project_id Tasks Project id
 * @apiSuccess {Integer}  tasks.time       Tasks total time in seconds
 * @apiSuccess {String}   tasks.start      Datetime of first Tasks Time Interval start_at
 * @apiSuccess {String}   tasks.end        Datetime of last Tasks Time Interval end_at
 * @apiSuccess {Object[]} total            Array of total tasks time
 * @apiSuccess {Integer}  total.time       Total time of tasks in seconds
 * @apiSuccess {String}   total.start      Datetime of first Time Interval start_at
 * @apiSuccess {String}   total.end        DateTime of last Time Interval end_at
 */
/**
 * @api {POST|GET} /api/v1/time/task Task
 * @apiParamExample {json} Request-Example:
 *  {
 *      "user_id":        1,
 *      "task_id":        1,
 *      "project_id":     2,
 *      "start_at":       "2005-01-01 00:00:00",
 *      "end_at":         "2019-01-01 00:00:00",
 *      "count_mouse":    [">=", 30],
 *      "count_keyboard": ["<=", 200],
 *      "id":             [">", 1]
 *  }
 * @apiUse RelationsExample
 * @apiDescription Get task and its total time
 * @apiVersion 0.1.0
 * @apiName Task
 * @apiGroup Time
 *
 * @apiParam {Integer}  task_id                       TimeInterval Task id
 * @apiParam {Integer}  [project_id]     `QueryParam` TimeInterval Task Project id
 * @apiParam {Integer}  [user_id]        `QueryParam` TimeInterval Task User id
 * @apiParam {String}   [start_at]                    TimeInterval Start DataTime
 * @apiParam {String}   [end_at]                      TimeInterval End DataTime
 * @apiParam {Integer}  [count_mouse]    `QueryParam` TimeInterval Count mouse
 * @apiParam {Integer}  [count_keyboard] `QueryParam` TimeInterval Count keyboard
 * @apiParam {Integer}  [id]             `QueryParam` TimeInterval id
 * @apiUse Relations
 *
 * @apiSuccess {String}   current_datetime Current datetime of server
 * @apiSuccess {Object[]} tasks            Tasks
 * @apiSuccess {Integer}  tasks.id         Task id
 * @apiSuccess {Integer}  tasks.user_id    Task User id
 * @apiSuccess {Integer}  tasks.project_id Task Project id
 * @apiSuccess {Integer}  tasks.time       Task total time in seconds
 * @apiSuccess {String}   tasks.start      Datetime of first Tasks's Time Interval's start_at
 * @apiSuccess {String}   tasks.end        Datetime of last Tasks's Time Interval's end_at
 * @apiSuccess {Object[]} total            Total tasks time
 * @apiSuccess {Integer}  total.time       Total time of tasks in seconds
 * @apiSuccess {String}   total.start      Datetime of first Time Interval's start_at
 * @apiSuccess {String}   total.end        DateTime of last Time Interval's end_at
 *
 * @apiError (Error 400) {String} error    Name of error
 * @apiError (Error 400) {String} reason   Reason of error
 */
/**
 * @api {POST|GET} /api/v1/time/task-user TaskUser
 * @apiParamExample {json} Request-Example:
 *  {
 *      "user_id":        1,
 *      "task_id":        1,
 *      "start_at":       [">=", "2005-01-01 00:00:00"],
 *      "end_at":         ["<=", "2019-01-01 00:00:00"],
 *      "count_mouse":    [">=", 30],
 *      "count_keyboard": ["<=", 200],
 *      "id":             [">", 1]
 *  }
 * @apiUse RelationsExample
 * @apiDescription Get time of user's single task
 * @apiVersion 0.1.0
 * @apiName TaskAndUser
 * @apiGroup Time
 *
 * @apiParam {Integer}  task_id                       TimeInterval Task ID
 * @apiParam {Integer}  user_id                       TimeInterval Task User ID
 * @apiParam {String}   [start_at]       `QueryParam` TimeInterval Start DataTime
 * @apiParam {String}   [end_at]         `QueryParam` TimeInterval End DataTime
 * @apiParam {Integer}  [count_mouse]    `QueryParam` TimeInterval Count mouse
 * @apiParam {Integer}  [count_keyboard] `QueryParam` TimeInterval Count keyboard
 * @apiParam {Integer}  [id]             `QueryParam` TimeInterval ID
 * @apiUse Relations
 *
 * @apiSuccess {DateTime} current_datetime Current datetime of server
 * @apiSuccess {Integer}  id               Task id
 * @apiSuccess {Integer}  user_id          Task's User id
 * @apiSuccess {Integer}  time             Total time of task in seconds
 * @apiSuccess {String}   start            Datetime of first Task's Time Interval's start_at
 * @apiSuccess {String}   end              DateTime of last Task's Time Interval's end_at
 *
 * @apiError (Error 400) {String} error  Name of error
 * @apiError (Error 400) {String} reason Reason of error
 */
/**
 * @api {post} /api/v1/tasks/list List
 * @apiDescription Get list of Tasks
 * @apiVersion 0.1.0
 * @apiName List
 * @apiGroup Task
 *
 * @apiParam {Integer}  [id]          `QueryParam` Task ID
 * @apiParam {Integer}  [project_id]  `QueryParam` Task Project
 * @apiParam {String}   [task_name]   `QueryParam` Task Name
 * @apiParam {String}   [description] `QueryParam` Task Description
 * @apiParam {String}   [url]         `QueryParam` Task Url
 * @apiParam {Integer}  [active]                   Is Task active. Available value: {0,1}
 * @apiParam {Integer}  [user_id]     `QueryParam` Task User
 * @apiParam {Integer}  [assigned_by] `QueryParam` User who assigned task
 * @apiParam {String}   [created_at]  `QueryParam` Task Creation DateTime
 * @apiParam {String}   [updated_at]  `QueryParam` Last Task update DataTime
 * @apiUse TaskRelations
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id":          [">", 1]
 *      "project_id":  ["=", [1,2,3]],
 *      "active":      1,
 *      "user_id":     ["=", [1,2,3]],
 *      "assigned_by": ["=", [1,2,3]],
 *      "task_name":   ["like", "%lorem%"],
 *      "description": ["like", "%lorem%"],
 *      "url":         ["like", "%lorem%"],
 *      "created_at":  [">", "2019-01-01 00:00:00"],
 *      "updated_at":  ["<", "2019-01-01 00:00:00"]
 *  }
 *
 * @apiUse TaskRelationsExample
 *
 * @apiSuccess {Object[]} TaskList                     Tasks
 * @apiSuccess {Object}   TaskList.Task                Task
 * @apiSuccess {Integer}  TaskList.Task.id             Task id
 * @apiSuccess {Integer}  TaskList.Task.project_id     Task Project id
 * @apiSuccess {Integer}  TaskList.Task.user_id        Task User id
 * @apiSuccess {Integer}  TaskList.Task.active         Task is active
 * @apiSuccess {String}   TaskList.Task.task_name      Task name
 * @apiSuccess {String}   TaskList.Task.description    Task description
 * @apiSuccess {String}   TaskList.Task.url            Task url
 * @apiSuccess {String}   TaskList.Task.created_at     Task date time of create
 * @apiSuccess {String}   TaskList.Task.updated_at     Task date time of update
 * @apiSuccess {String}   TaskList.Task.deleted_at     Task date time of delete
 * @apiSuccess {Object[]} TaskList.Task.time_intervals Task Time intervals
 * @apiSuccess {Object[]} TaskList.Task.user           Task User object
 * @apiSuccess {Object[]} TaskList.Task.assigned       Task assigned User object
 * @apiSuccess {Object[]} TaskList.Task.project        Task Project object
 */
/**
 * @api {post} /api/v1/tasks/create Create
 * @apiDescription Create Task
 * @apiVersion 0.1.0
 * @apiName Create
 * @apiGroup Task
 *
 * @apiParam {Integer} [project_id]  Task Project
 * @apiParam {String}  [task_name]   Task Name
 * @apiParam {String}  [description] Task Description
 * @apiParam {String}  url           Task Url
 * @apiParam {Integer} [active]      Active/Inactive Task. Available value: {0,1}
 * @apiParam {Integer} [user_id]     Task User
 * @apiParam {Integer} [assigned_by] User who assigned task
 * @apiParam {Integer} [priority_id] Task Priority ID
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "project_id":"163",
 *      "task_name":"retr",
 *      "description":"fdgfd",
 *      "active":1,
 *      "user_id":"3",
 *      "assigned_by":"1",
 *      "url":"URL",
 *      "priority_id": 1
 *  }
 *
 * @apiSuccess {Object}   res                Task object
 * @apiSuccess {Integer}  res.id             Task ID
 * @apiSuccess {Integer}  res.project_id     Task Project ID
 * @apiSuccess {Integer}  res.user_id        Task User ID
 * @apiSuccess {Integer}  res.active         Task active status
 * @apiSuccess {String}   res.task_name      Task name
 * @apiSuccess {String}   res.description    Task description
 * @apiSuccess {String}   res.url            Task url
 * @apiSuccess {String}   res.created_at     Task date time of create
 * @apiSuccess {String}   res.updated_at     Task date time of update
 *
 * @apiUse DefaultCreateErrorResponse
 */
/**
 * @api {post} /api/v1/tasks/show Show
 * @apiDescription Show Task
 * @apiVersion 0.1.0
 * @apiName Show
 * @apiGroup Task
 *
 * @apiParam {Integer}  id                         Task id
 * @apiParam {Integer}  [project_id]  `QueryParam` Task Project
 * @apiParam {String}   [task_name]   `QueryParam` Task Name
 * @apiParam {String}   [description] `QueryParam` Task Description
 * @apiParam {String}   [url]         `QueryParam` Task Url
 * @apiParam {Integer}  [active]                   Is Task active. Available value: {0,1}
 * @apiParam {Integer}  [user_id]     `QueryParam` Task's User
 * @apiParam {Integer}  [assigned_by] `QueryParam` User who assigned task
 * @apiParam {String}   [created_at]  `QueryParam` Task Creation DateTime
 * @apiParam {String}   [updated_at]  `QueryParam` Last Task update DataTime
 * @apiUse TaskRelations
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id":          1,
 *      "project_id":  ["=", [1,2,3]],
 *      "active":      1,
 *      "user_id":     ["=", [1,2,3]],
 *      "assigned_by": ["=", [1,2,3]],
 *      "task_name":   ["like", "%lorem%"],
 *      "description": ["like", "%lorem%"],
 *      "url":         ["like", "%lorem%"],
 *      "created_at":  [">", "2019-01-01 00:00:00"],
 *      "updated_at":  ["<", "2019-01-01 00:00:00"]
 *  }
 * @apiUse TaskRelationsExample
 *
 * @apiSuccess {Object}   Task                Task
 * @apiSuccess {Integer}  Task.id             Task id
 * @apiSuccess {Integer}  Task.project_id     Task Project id
 * @apiSuccess {Integer}  Task.user_id        Task User id
 * @apiSuccess {Integer}  Task.active         Task active status
 * @apiSuccess {String}   Task.task_name      Task name
 * @apiSuccess {String}   Task.description    Task description
 * @apiSuccess {String}   Task.url            Task url
 * @apiSuccess {String}   Task.created_at     Task date time of create
 * @apiSuccess {String}   Task.updated_at     Task date time of update
 * @apiSuccess {String}   Task.deleted_at     Task date time of delete
 * @apiSuccess {Object[]} Task.time_intervals Task Users
 * @apiSuccess {Object[]} Task.user           Task User object
 * @apiSuccess {Object[]} Task.assigned       Task assigned User object
 * @apiSuccess {Object[]} Task.project        Task Project
 *
 * @apiUse DefaultShowErrorResponse
 */
/**
 * @api {post} /api/v1/tasks/edit Edit
 * @apiDescription Edit Task
 * @apiVersion 0.1.0
 * @apiName Edit
 * @apiGroup Task
 *
 * @apiParam {Integer}  id          Task id
 * @apiParam {Integer}  project_id  Task Project
 * @apiParam {String}   task_name   Task Name
 * @apiParam {String}   description Task Description
 * @apiParam {String}   [url]       Task Url
 * @apiParam {Integer}  active      Is Task active. Available value: {0,1}
 * @apiParam {Integer}  user_id     Task User
 * @apiParam {Integer}  assigned_by User who assigned task
 * @apiParam {Integer}  priority_id Task Priority ID
 * @apiUse TaskRelations
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id":          1,
 *      "project_id":  2,
 *      "active":      1,
 *      "user_id":     3,
 *      "assigned_by": 2,
 *      "task_name":   "lorem",
 *      "description": "test",
 *      "url":         "url",
 *      "priority_id": 1
 *  }
 * @apiUse TaskRelationsExample
 *
 * @apiSuccess {Object}   res                Task object
 * @apiSuccess {Integer}  res.id             Task ID
 * @apiSuccess {Integer}  res.project_id     Task Project ID
 * @apiSuccess {Integer}  res.user_id        Task User ID
 * @apiSuccess {Integer}  res.active         Task active status
 * @apiSuccess {String}   res.task_name      Task name
 * @apiSuccess {String}   res.description    Task description
 * @apiSuccess {String}   res.url            Task url
 * @apiSuccess {String}   res.created_at     Task date time of create
 * @apiSuccess {String}   res.updated_at     Task date time of update
 * @apiSuccess {String}   res.deleted_at     Task date time of delete
 *
 * @apiUse DefaultEditErrorResponse
 */
/**
 * @api {post} /api/v1/tasks/remove Destroy
 * @apiDescription Destroy Task
 * @apiVersion 0.1.0
 * @apiName Destroy
 * @apiGroup Task
 *
 * @apiParam {String} id Task Id
 *
 * @apiUse DefaultDestroyRequestExample
 * @apiUse DefaultDestroyResponse
 */
/**
 * @api {post} /api/v1/tasks/dashboard Dashboard
 * @apiDescription Display task for dashboard
 * @apiVersion 0.1.0
 * @apiName Dashboard
 * @apiGroup Task
 *
 * @apiParam {Integer}  [id]          `QueryParam` Task ID
 * @apiParam {Integer}  [project_id]  `QueryParam` Task Project
 * @apiParam {String}   [task_name]   `QueryParam` Task Name
 * @apiParam {String}   [description] `QueryParam` Task Description
 * @apiParam {String}   [url]         `QueryParam` Task Url
 * @apiParam {Integer}  [active]                   Active/Inactive Task. Available value: {0,1}
 * @apiParam {Integer}  [user_id]     `QueryParam` Task's User
 * @apiParam {Integer}  [assigned_by] `QueryParam` User who assigned task
 * @apiParam {String}   [created_at]  `QueryParam` Task Creation DateTime
 * @apiParam {String}   [updated_at]  `QueryParam` Last Task update DataTime
 * @apiUse TaskRelations
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id":          [">", 1]
 *      "project_id":  ["=", [1,2,3]],
 *      "active":      1,
 *      "user_id":     ["=", [1,2,3]],
 *      "assigned_by": ["=", [1,2,3]],
 *      "task_name":   ["like", "%lorem%"],
 *      "description": ["like", "%lorem%"],
 *      "url":         ["like", "%lorem%"],
 *      "created_at":  [">", "2019-01-01 00:00:00"],
 *      "updated_at":  ["<", "2019-01-01 00:00:00"]
 *  }
 * @apiUse TaskRelationsExample
 *
 * @apiSuccess {Object[]} array                       Tasks
 * @apiSuccess {Object}   array.object                Task object
 * @apiSuccess {Integer}  array.object.id             Task ID
 * @apiSuccess {Integer}  array.object.project_id     Task Project ID
 * @apiSuccess {Integer}  array.object.user_id        Task User ID
 * @apiSuccess {Integer}  array.object.active         Task active status
 * @apiSuccess {String}   array.object.task_name      Task name
 * @apiSuccess {String}   array.object.description    Task description
 * @apiSuccess {String}   array.object.url            Task url
 * @apiSuccess {String}   array.object.created_at     Task date time of create
 * @apiSuccess {String}   array.object.updated_at     Task date time of update
 * @apiSuccess {String}   array.object.deleted_at     Task date time of delete
 * @apiSuccess {Time}     array.object.total_time     Task total time
 * @apiSuccess {Object[]} array.object.time_intervals Task TimeIntervals
 * @apiSuccess {Object}   array.object.user           Task User object
 * @apiSuccess {Object[]} array.object.assigned       Task assigned User
 * @apiSuccess {Object}   array.object.project        Task Project
 */
/**
 * @api {post} /api/v1/task-comment/create Create
 * @apiDescription Create Task Comment
 * @apiVersion 0.1.0
 * @apiName CreateTaskComment
 * @apiGroup Task Comment
 *
 * @apiUse UnauthorizedError
 *
 * @apiParamExample {json} Request Example
 * {
 *   "task_id": 1,
 *   "user_id": 1,
 *   "start_at": "2013-04-12T16:40:00-04:00",
 *   "end_at": "2013-04-12T16:40:00-04:00"
 * }
 *
 * @apiSuccessExample {json} Answer Example
 * {
 *   "comment": {
 *     "id": 2251,
 *     "task_id": 1,
 *     "start_at": "2013-04-12 20:40:00",
 *     "end_at": "2013-04-12 20:40:00",
 *     "created_at": "2018-10-01 03:20:59",
 *     "updated_at": "2018-10-01 03:20:59",
 *     "count_mouse": 0,
 *     "count_keyboard": 0,
 *     "user_id": 1
 *   }
 * }
 *
 * @apiParam {Integer}  task_id   Task id
 * @apiParam {String}   content  Comment content
 *
 * @apiUse WrongDateTimeFormatStartEndAt
 */
/**
 * @api {post} /api/v1/task-comment/list List
 * @apiDescription Get list of Task Comments
 * @apiVersion 0.1.0
 * @apiName GetTaskCommentList
 * @apiGroup Task Comment
 *
 * @apiParam {Integer}  [id]         `QueryParam` Task Comment id
 * @apiParam {Integer}  [task_id]    `QueryParam` Task Comment Task id
 * @apiParam {Integer}  [user_id]    `QueryParam` Task Comment User id
 * @apiParam {String}   [start_at]   `QueryParam` Task Comment Start DataTime
 * @apiParam {String}   [end_at]     `QueryParam` Task Comment End DataTime
 * @apiParam {String}   [created_at] `QueryParam` Task Comment Creation DateTime
 * @apiParam {String}   [updated_at] `QueryParam` Last Task Comment data update DataTime
 * @apiParam {String}   [deleted_at] `QueryParam` When Task Comment was deleted (null if not)
 *
 * @apiSuccess (200) {Object[]} TaskCommentList Task Comment
 *
 * @apiSuccessExample {json} Answer Example:
 * {
 *      {
 *          "id":1,
 *          "task_id":1,
 *          "start_at":"2006-06-20 15:54:40",
 *          "end_at":"2006-06-20 15:59:38",
 *          "created_at":"2018-10-15 05:54:39",
 *          "updated_at":"2018-10-15 05:54:39",
 *          "deleted_at":null,
 *          "count_mouse":42,
 *          "count_keyboard":43,
 *          "user_id":1
 *      },
 *      ...
 * }
 *
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/task-comment/show Show
 * @apiDescription Show Task Comment
 * @apiVersion 0.1.0
 * @apiName ShowTaskComment
 * @apiGroup Task Comment
 *
 * @apiParam {Integer}  id     Task Comment id
 *
 * @apiParamExample {json} Request Example
 * {
 *   "id": 1
 * }
 *
 * @apiSuccess {Object}  object TaskComment
 * @apiSuccess {Integer} object.id
 *
 * @apiSuccessExample {json} Answer Example
 * {
 *   "id": 1,
 *   "task_id": 1,
 *   "start_at": "2006-05-31 16:15:09",
 *   "end_at": "2006-05-31 16:20:07",
 *   "created_at": "2018-09-25 06:15:08",
 *   "updated_at": "2018-09-25 06:15:08",
 *   "deleted_at": null,
 *   "count_mouse": 88,
 *   "count_keyboard": 127,
 *   "user_id": 1
 * }
 *
 * @apiUse UnauthorizedError
 */
/**
 * @api {delete, post} /api/v1/task-comment/remove Destroy
 * @apiDescription Destroy Task Comment
 * @apiVersion 0.1.0
 * @apiName DestroyTaskComment
 * @apiGroup Task Comment
 *
 * @apiParam {Integer}   id Task Comment id
 *
 * @apiSuccess {String} message Message
 *
 * @apiSuccessExample {json} Answer Example
 * {
 *   "message":"Item has been removed"
 * }
 *
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/screenshots/list List
 * @apiDescription Get list of Screenshots
 * @apiVersion 0.1.0
 * @apiName List
 * @apiGroup Screenshot
 *
 * @apiParam {Integer}  [id]               `QueryParam` Screenshot ID
 * @apiParam {Integer}  [time_interval_id] `QueryParam` Screenshot's Time Interval ID
 * @apiParam {Integer}  [user_id]          `QueryParam` Screenshot's TimeInterval's User ID
 * @apiParam {Integer}  [project_id]       `QueryParam` Screenshot's TimeInterval's Project ID
 * @apiParam {String}   [path]             `QueryParam` Image path URI
 * @apiParam {DateTime} [created_at]       `QueryParam` Screenshot Creation DateTime
 * @apiParam {DateTime} [updated_at]       `QueryParam` Last Screenshot data update DataTime
 * @apiUse ScreenshotRelations
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id":               [">", 1],
 *      "time_interval_id": ["=", [1,2,3]],
 *      "user_id":          ["=", [1,2,3]],
 *      "project_id":       ["=", [1,2,3]],
 *      "path":             ["like", "%lorem%"],
 *      "created_at":       [">", "2019-01-01 00:00:00"],
 *      "updated_at":       ["<", "2019-01-01 00:00:00"]
 *  }
 *
 * @apiUse ScreenshotRelationsExample
 * @apiUse UnauthorizedError
 *
 * @apiSuccess {Object[]} ScreenshotList                             Screenshots (Array of objects)
 * @apiSuccess {Object}   ScreenshotList.Screenshot                  Screenshot object
 * @apiSuccess {Integer}  ScreenshotList.Screenshot.id               Screenshot's ID
 * @apiSuccess {Integer}  ScreenshotList.Screenshot.time_interval_id Screenshot's Time Interval ID
 * @apiSuccess {String}   ScreenshotList.Screenshot.path             Screenshot's Image path URI
 * @apiSuccess {DateTime} ScreenshotList.Screenshot.created_at       Screenshot's date time of create
 * @apiSuccess {DateTime} ScreenshotList.Screenshot.updated_at       Screenshot's date time of update
 * @apiSuccess {DateTime} ScreenshotList.Screenshot.deleted_at       Screenshot's date time of delete
 * @apiSuccess {Object}   ScreenshotList.Screenshot.time_interval    Screenshot's Task
 */
/**
 * @api {post} /api/v1/screenshots/create Create
 * @apiDescription Create Screenshot
 * @apiVersion 0.1.0
 * @apiName Create
 * @apiGroup Screenshot
 *
 * @apiParam {Integer} time_interval_id  Screenshot's Time Interval ID
 * @apiParam {Binary}  screenshot        Screenshot file
 *
 * @apiParamExample {json} Simple-Request Example
 *  {
 *      "time_interval_id": 1,
 *      "screenshot": ```binary data```
 *  }
 *
 * @apiSuccess {Object}   Screenshot                  Screenshot object
 * @apiSuccess {Integer}  Screenshot.id               Screenshot id
 * @apiSuccess {Integer}  Screenshot.time_interval_id Screenshot Time Interval id
 * @apiSuccess {String}   Screenshot.path             Screenshot Image path URI
 * @apiSuccess {String}   Screenshot.created_at       Screenshot date time of create
 * @apiSuccess {String}   Screenshot.updated_at       Screenshot date time of update
 * @apiSuccess {Boolean}  Screenshot.important        Screenshot important flag
 *
 * @apiUse DefaultCreateErrorResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/screenshots/bulk-create Bulk Create
 * @apiDescription Create Screenshot
 * @apiVersion 0.1.0
 * @apiName Bulk Create
 * @apiGroup Screenshot
 *
 * @apiSuccess {Object[]} messages                  Messages
 * @apiSuccess {Integer}  messages.id               Screenshot id
 * @apiSuccess {Integer}  messages.time_interval_id Screenshot Time Interval id
 * @apiSuccess {String}   messages.path             Screenshot Image path URI
 * @apiSuccess {String}   messages.created_at       Screenshot date time of create
 * @apiSuccess {String}   messages.updated_at       Screenshot date time of update
 * @apiSuccess {Boolean}  messages.important        Screenshot important flag
 *
 * @apiError (400)  {Object[]} messages         Messages
 * @apiError (400)  {String}   messages.error   Error title
 * @apiError (400)  {String}   messages.reason  Error reason
 * @apiError (400)  {String}   messages.code    Error code
 *
 * @apiUse DefaultCreateErrorResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/screenshots/show Show
 * @apiDescription Show Screenshot
 * @apiVersion 0.1.0
 * @apiName Show
 * @apiGroup Screenshot
 *
 * @apiParam {Integer}  id                              Screenshot id
 * @apiParam {Integer}  [time_interval_id] `QueryParam` Screenshot Time Interval id
 * @apiParam {String}   [path]             `QueryParam` Image path URI
 * @apiParam {String}   [created_at]       `QueryParam` Screenshot Creation DateTime
 * @apiParam {String}   [updated_at]       `QueryParam` Last Screenshot data update DataTime
 * @apiUse ScreenshotRelations
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id":               1,
 *      "time_interval_id": ["=", [1,2,3]],
 *      "path":             ["like", "%lorem%"],
 *      "created_at":       [">", "2019-01-01 00:00:00"],
 *      "updated_at":       ["<", "2019-01-01 00:00:00"]
 *  }
 * @apiUse ScreenshotRelationsExample
 *
 * @apiSuccess {Object}   Screenshot                  Screenshot object
 * @apiSuccess {Integer}  Screenshot.id               Screenshot id
 * @apiSuccess {Integer}  Screenshot.time_interval_id Screenshot Time Interval id
 * @apiSuccess {String}   Screenshot.path             Screenshot Image path URI
 * @apiSuccess {String}   Screenshot.created_at       Screenshot date time of create
 * @apiSuccess {String}   Screenshot.updated_at       Screenshot date time of update
 * @apiSuccess {String}   Screenshot.deleted_at       Screenshot date time of delete
 * @apiSuccess {Object}   Screenshot.time_interval    Screenshot Task
 *
 * @apiUse DefaultShowErrorResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/screenshots/edit Edit
 * @apiDescription Edit Screenshot
 * @apiVersion 0.1.0
 * @apiName Edit
 * @apiGroup Screenshot
 * @apiParam {Integer}  id               Screenshot id
 * @apiParam {Integer}  time_interval_id Screenshot Time Interval id
 * @apiParam {String}   path             Image path URI
 * @apiParam {DateTime} [created_at]     Screenshot Creation DateTime
 * @apiParam {DateTime} [updated_at]     Last Screenshot data update DataTime
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id":               1,
 *      "time_interval_id": 2,
 *      "path":             "test"
 *  }
 *
 * @apiSuccess {Object}   Screenshot                  Screenshot object
 * @apiSuccess {Integer}  Screenshot.id               Screenshot ID
 * @apiSuccess {Integer}  Screenshot.time_interval_id Screenshot Time Interval ID
 * @apiSuccess {String}   Screenshot.path             Screenshot Image path URI
 * @apiSuccess {String}   Screenshot.created_at       Screenshot date time of create
 * @apiSuccess {String}   Screenshot.updated_at       Screenshot date time of update
 * @apiSuccess {String}   Screenshot.deleted_at       Screenshot date time of delete
 *
 * @apiUse DefaultEditErrorResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/screenshots/remove Destroy
 * @apiUse DefaultDestroyRequestExample
 * @apiDescription Destroy Screenshot
 * @apiVersion 0.1.0
 * @apiName Destroy
 * @apiGroup Screenshot
 *
 * @apiParam {String} id Screenshot id
 *
 * @apiUse DefaultDestroyResponse
 */
/**
 * @api {post} /api/v1/screenshots/dashboard Dashboard
 * @apiDescription Get dashboard of Screenshots
 * @apiVersion 0.1.0
 * @apiName Dashboard
 * @apiGroup Screenshot
 *
 * @apiParam {Integer}  [id]               `QueryParam` Screenshot ID
 * @apiParam {Integer}  [time_interval_id] `QueryParam` Screenshot's Time Interval ID
 * @apiParam {Integer}  [user_id]          `QueryParam` Screenshot's User ID
 * @apiParam {String}   [path]             `QueryParam` Image path URI
 * @apiParam {DateTime} [created_at]       `QueryParam` Screenshot Creation DateTime
 * @apiParam {DateTime} [updated_at]       `QueryParam` Last Screenshot data update DataTime
 * @apiUse ScreenshotRelations
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id":               1,
 *      "time_interval_id": ["=", [1,2,3]],
 *      "user_id":          ["=", [1,2,3]],
 *      "project_id":       ["=", [1,2,3]],
 *      "path":             ["like", "%lorem%"],
 *      "created_at":       [">", "2019-01-01 00:00:00"],
 *      "updated_at":       ["<", "2019-01-01 00:00:00"]
 *  }
 * @apiUse ScreenshotRelationsExample
 * @apiUse UnauthorizedError
 *
 * @apiSuccess {Object[]} Array                                            Array of objects
 * @apiSuccess {String}   Array.object.interval                            Time of interval
 * @apiSuccess {Object[]} Array.object.screenshots                         Screenshots of interval (Array of objects, 6 indexes)
 * @apiSuccess {Integer}  Array.object.screenshots.object.id               Screenshot ID
 * @apiSuccess {Integer}  Array.object.screenshots.object.time_interval_id Screenshot Time Interval ID
 * @apiSuccess {String}   Array.object.screenshots.object.path             Screenshot Image path URI
 * @apiSuccess {String}   Array.object.screenshots.object.created_at       Screenshot date time of create
 * @apiSuccess {String}   Array.object.screenshots.object.updated_at       Screenshot date time of update
 * @apiSuccess {String}   Array.object.screenshots.object.deleted_at       Screenshot date time of delete
 * @apiSuccess {Object}   Array.object.screenshots.object.time_interval    Screenshot Task
 */
/**
 * @api {post} /api/v1/rules/edit Edit
 * @apiDescription Edit Rule
 * @apiVersion 0.1.0
 * @apiName EditRule
 * @apiGroup Rule
 *
 * @apiParam {Integer} role_id Role id
 * @apiParam {String}  object  Object name
 * @apiParam {String}  action  Action name
 * @apiParam {Boolean} allow   Allow status
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "role_id": 2,
 *      "object": "projects",
 *      "action": "create",
 *      "allow": 1
 *  }
 *
 * @apiSuccess {String} message OK
 *
 * @apiUse DefaultEditErrorResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/rules/bulk-edit Bulk Edit
 * @apiDescription Editing Multiple Rules
 * @apiVersion 0.1.0
 * @apiName bulkEditRules
 * @apiGroup Rule
 *
 * @apiParam {Object[]} rules                Rules
 * @apiParam {Object}   rules.object         Rule
 * @apiParam {Integer}  rules.object.role_id Role id
 * @apiParam {String}   rules.object.object  Rule object name
 * @apiParam {String}   rules.object.action  Rule action name
 * @apiParam {Boolean}  rules.object.allow   Rule allow status
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "rules":
 *      [
 *          {
 *              "role_id": 2,
 *              "object": "projects",
 *              "action": "create",
 *              "allow": 0
 *          },
 *          {
 *              "role_id": 2,
 *              "object": "projects",
 *              "action": "list",
 *              "allow": 0
 *          }
 *      ]
 *  }
 *
 * @apiSuccess {String[]} messages         Messages
 * @apiSuccess {String}   messages.message OK
 *
 * @apiSuccessExample {json} Response example
 * {
 * timeInterval,timeInterval.task
 * }
 *
 * @apiUse DefaultEditErrorResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {get} /api/v1/rules/actions Actions
 * @apiDescription Get list of Rules Actions
 * @apiVersion 0.1.0
 * @apiName GetRulesActions
 * @apiGroup Rule
 *
 * @apiSuccessExample {json} Response example
 * [
 *   {
 *     "object": "projects",
 *     "action": "list",
 *     "name": "Project list"
 *   },
 *   {
 *     "object": "projects",
 *     "action": "create",
 *     "name": "Project create"
 *   },
 *   {
 *     "object": "projects",
 *     "action": "show",
 *     "name": "Project show"
 *   }
 * ]
 *
 * @apiSuccess (200) {Object[]} actions               Actions
 * @apiSuccess (200) {Object}   actions.action        Applied to
 * @apiSuccess (200) {String}   actions.action.object Applied action
 * @apiSuccess (200) {String}   actions.action.action Action type
 * @apiSuccess (200) {String}   actions.action.string Action name
 *
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/roles/list List
 * @apiDescription Get list of Roles
 * @apiVersion 0.1.0
 * @apiName GetRolesList
 * @apiGroup Role
 *
 * @apiParam {Integer}  [id]          `QueryParam` Role ID
 * @apiParam {Integer}  [user_id]     `QueryParam` Role's Users ID
 * @apiParam {String}   [name]        `QueryParam` Role Name
 * @apiParam {String} [created_at]    `QueryParam` Role Creation DateTime
 * @apiParam {String} [updated_at]    `QueryParam` Last Role update DataTime
 * @apiUse RolesRelations
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id":          [">", 1]
 *      "user_id":     ["=", [1,2,3]],
 *      "name":        ["like", "%lorem%"],
 *      "created_at":  [">", "2019-01-01 00:00:00"],
 *      "updated_at":  ["<", "2019-01-01 00:00:00"]
 *  }
 * @apiUse RolesRelationsExample
 *
 * @apiSuccessExample {json} Simple response example
 * [
 *   {
 *     "id": 256,
 *     "name": "test",
 *     "deleted_at": null,
 *     "created_at": "2018-10-12 11:44:08",
 *     "updated_at": "2018-10-12 11:44:08"
 *   }
 * ]
 *
 * @apiSuccess {Object[]} RoleList                  Roles
 * @apiSuccess {Object}   RoleList.Role             Role object
 * @apiSuccess {Integer}  RoleList.Role.id          Role ID
 * @apiSuccess {String}   RoleList.Role.name        Role name
 * @apiSuccess {String}   RoleList.Role.created_at  Role date time of create
 * @apiSuccess {String}   RoleList.Role.updated_at  Role date time of update
 * @apiSuccess {String}   RoleList.Role.deleted_at  Role date time of delete
 * @apiSuccess {Object[]} RoleList.Role.users       Role User
 * @apiSuccess {Object[]} RoleList.Role.rules       Role Task
 *
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/roles/create Create
 * @apiDescription Create Role
 * @apiVersion 0.1.0
 * @apiName CreateRole
 * @apiGroup Role
 *
 * @apiParam {String} name Roles's name
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "name": "test"
 *  }
 *
 * @apiSuccessExample {json} Answer Example
 * {
 *   "res": {
 *     "name": "test",
 *     "updated_at": "2018-10-12 11:44:08",
 *     "created_at": "2018-10-12 11:44:08",
 *     "id": 256
 *    }
 * }
 *
 * @apiSuccess {Object}   res             Response object
 * @apiSuccess {Integer}  res.id          Role ID
 * @apiSuccess {String}   res.name        Role name
 * @apiSuccess {String}   res.created_at  Role date time of create
 * @apiSuccess {String}   res.updated_at  Role date time of update
 *
 * @apiUse DefaultCreateErrorResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/roles/show Show
 * @apiDescription Get Role Entity
 * @apiVersion 0.1.0
 * @apiName ShowRole
 * @apiGroup Role
 *
 * @apiParam {Integer}    id                        Role id
 * @apiParam {String}     [name]       `QueryParam` Role Name
 * @apiParam {String}     [created_at] `QueryParam` Role date time of create
 * @apiParam {String}     [updated_at] `QueryParam` Role date time of update
 *
 * @apiUse RolesRelations
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id":          1,
 *      "name":        ["like", "%lorem%"],
 *      "description": ["like", "%lorem%"],
 *      "created_at":  [">", "2019-01-01 00:00:00"],
 *      "updated_at":  ["<", "2019-01-01 00:00:00"]
 *  }
 *
 * @apiUse RolesRelationsExample
 *
 * @apiSuccess {Object}   Role             Role object
 * @apiSuccess {Integer}  Role.id          Role id
 * @apiSuccess {String}   Role.name        Role name
 * @apiSuccess {String}   Role.created_at  Role date time of create
 * @apiSuccess {String}   Role.updated_at  Role date time of update
 * @apiSuccess {String}   Role.deleted_at  Role date time of delete
 * @apiSuccess {Object[]} Role.users       Role User
 * @apiSuccess {Object[]} Role.rules       Role Task
 *
 * @apiSuccessExample {json} Answer Relations Example
 * {
 *   "id": 1,
 *   "name": "root",
 *   "deleted_at": null,
 *   "created_at": "2018-09-25 06:15:07",
 *   "updated_at": "2018-09-25 06:15:07"
 * }
 *
 * @apiUse DefaultShowErrorResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/roles/edit Edit
 * @apiDescription Edit Role
 * @apiVersion 0.1.0
 * @apiName EditRole
 * @apiGroup Role
 *
 * @apiParam {Integer} id   Role ID
 * @apiParam {String}  name Role Name
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id": 1,
 *      "name": "test"
 *  }
 *
 * @apiSuccess {Object}   Role            Role object
 * @apiSuccess {Integer}  Role.id         Role ID
 * @apiSuccess {String}   Role.name       Role name
 * @apiSuccess {String}   Role.created_at Role date time of create
 * @apiSuccess {String}   Role.updated_at Role date time of update
 * @apiSuccess {String}   Role.deleted_at Role date time of delete
 *
 * @apiUse DefaultEditErrorResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/roles/remove Destroy
 * @apiUse DefaultDestroyRequestExample
 * @apiDescription Destroy Role
 * @apiVersion 0.1.0
 * @apiName DestroyRole
 * @apiGroup Role
 *
 * @apiParam {Integer} id Role id
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id": 1
 *  }
 *
 * @apiUse DefaultDestroyResponse
 * @apiUse UnauthorizedError
 */
/**
 * @api {post} /api/v1/roles/allowed-rules Allowed Rules
 * @apiDescription Get Rule allowed action list
 * @apiVersion 0.1.0
 * @apiName GetRulesAllowedActionList
 * @apiGroup Role
 *
 * @apiParam {Integer} id Role id
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id": 1
 *  }
 *
 * @apiSuccess {Object[]} array               Rules
 * @apiSuccess {Object}   array.object        Rule
 * @apiSuccess {String}   array.object.object Object of rule
 * @apiSuccess {String}   array.object.action Action of rule
 * @apiSuccess {String}   array.object.name   Name of rule
 *
 * @apiSuccessExample {json} Answer Example
 * [
 *   {
 *     "object": "attached-users",
 *     "action": "bulk-create",
 *     "name": "Attached User relation multiple create"
 *   },
 *   {
 *     "object": "attached-users",
 *     "action": "bulk-remove",
 *     "name": "Attached User relation multiple remove"
 *   }
 * ]
 *
 * @apiError (Error 400) {String} error  Name of error
 * @apiError (Error 400) {String} reason Reason of error
 *
 * @apiUse UnauthorizedError
 *
 * @apiErrorExample {json} Invalid id Example
 * {
 *   "error": "Validation fail",
 *   "reason": "Invalid id"
 * }
 */
//----------------------------------------------------------------------------------------------------------------------
// Defines
//----------------------------------------------------------------------------------------------------------------------
/**
 * @apiDefine DefaultCreateErrorResponse
 *
 * @apiError (Error 400) {String} error  Name of error
 * @apiError (Error 400) {String} reason Reason of error
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine DefaultBulkCreateErrorResponse
 * @apiError (Error 200) {Object[]}  messages               Errors
 * @apiError (Error 200) {Object}    messages.object        Error object
 * @apiError (Error 200) {String}    messages.object.error  Name of error
 * @apiError (Error 200) {String}    messages.object.reason Reason of error
 * @apiError (Error 200) {Integer}   messages.object.code   Code of error
 *
 * @apiError (Error 400) {Object[]} messages                Errors
 * @apiError (Error 400) {Object}   messages.object         Error
 * @apiError (Error 400) {String}   messages.object.error   Name of error
 * @apiError (Error 400) {String}   messages.object.reason  Reason of error
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine DefaultShowErrorResponse
 * @apiError (Error 400) {String} error  Name of error
 * @apiError (Error 400) {String} reason Reason of error
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine DefaultEditErrorResponse
 *
 * @apiError (Error 400) {String} error  Error name
 * @apiError (Error 400) {String} reason Reason
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine AuthAnswer
 *
 * @apiSuccess {String}     access_token  Token
 * @apiSuccess {String}     token_type    Token Type
 * @apiSuccess {String}     expires_in    Token TTL in seconds
 * @apiSuccess {Array}      user          User Entity
 *
 * @apiSuccessExample {json} Answer Example
 *  {
 *      {
 *        "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciO...",
 *        "token_type": "bearer",
 *         "expires_in": 3600,
 *         "user": {
 *           "id": 42,
 *           "full_name": "Captain",
 *           "email": "johndoe@example.com",
 *           "url": "",
 *           "company_id": 41,
 *           "payroll_access": 1,
 *           "billing_access": 1,
 *           "avatar": "",
 *           "screenshots_active": 1,
 *           "manual_time": 0,
 *           "permanent_tasks": 0,
 *           "computer_time_popup": 300,
 *           "poor_time_popup": "",
 *           "blur_screenshots": 0,
 *           "web_and_app_monitoring": 1,
 *           "webcam_shots": 0,
 *           "screenshots_interval": 9,
 *           "active": "active",
 *           "deleted_at": null,
 *           "created_at": "2018-09-25 06:15:08",
 *           "updated_at": "2018-09-25 06:15:08",
 *           "timezone": null
 *         }
 *      }
 *  }
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine UnauthorizedError
 *
 * @apiErrorExample {json} Access Error Example
 * {
 *    "error":      "Access denied",
 *    "reason":     "not logged in",
 *    "error_code": "ERR_NO_AUTH"
 * }
 *
 * @apiErrorExample {json} Access Error Example
 * {
 *    "error": "Unauthorized"
 * }
 *
 * @apiError (Error 403) {String} error         Error name
 * @apiError (Error 403) {String} reason        Error description
 * @apiError (Error 403) {String} error_code    Error code
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine DefaultBulkEditErrorResponse
 *
 * Yes, we send errors with 200 HTTP status-code, because 207 use WebDAV
 * and REST API have some architecture problems
 *
 * @apiError (Error 200) {Object[]}  messages               Errors
 * @apiError (Error 200) {Object}    messages.object        Error
 * @apiError (Error 200) {String}    messages.object.error  Error name
 * @apiError (Error 200) {String}    messages.object.reason Reason
 * @apiError (Error 200) {Integer}   messages.object.code   Error Status-Code
 *
 * @apiError (Error 400) {Object[]} messages               Errors
 * @apiError (Error 400) {Object}   messages.object        Error
 * @apiError (Error 400) {String}   messages.object.error  Name of error
 * @apiError (Error 400) {String}   messages.object.reason Reason of error
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine DefaultDestroyRequestExample
 *
 * @apiParamExample {json} Simple Request Example
 *  {
 *      "id": 1
 *  }
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine DefaultDestroyResponse
 * @apiSuccess {String}    message      Message about success remove
 * @apiError   (Error 404) ItemNotFound HTTP/1.1 404 Page Not Found
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine DefaultBulkDestroyErrorResponse
 *
 * @apiError (Error 200) {Object[]}  messages               Errors
 * @apiError (Error 200) {Object}    messages.object        Error object
 * @apiError (Error 200) {String}    messages.object.error  Name of error
 * @apiError (Error 200) {String}    messages.object.reason Reason of error
 * @apiError (Error 200) {Integer}   messages.object.code   Code of error
 *
 * @apiError (Error 400) {Object[]} messages               Errors
 * @apiError (Error 400) {Object}   messages.object        Error object
 * @apiError (Error 400) {String}   messages.object.error  Name of error
 * @apiError (Error 400) {String}   messages.object.reason Reason of error
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine ProjectRelations
 *
 * @apiParam {String} [with]               For add relation model in response
 * @apiParam {Object} [tasks] `QueryParam` Project's relation task. All params in <a href="#api-Task-GetTaskList" >@Task</a>
 * @apiParam {Object} [users] `QueryParam` Project's relation user. All params in <a href="#api-User-GetUserList" >@User</a>
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine ProjectRelationsExample
 * @apiParamExample {json} Request With Relations Example
 *  {
 *      "with":            "tasks,users,tasks.timeIntervals",
 *      "tasks.id":        [">", 1],
 *      "tasks.active":    1,
 *      "users.full_name": ["like", "%lorem%"],
 *      "id":              1
 *  }
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine ProjectUserRelations
 * @apiParam {Object} [user]    `QueryParam` ProjectUser's relation user. All params in <a href="#api-User-GetUserList" >@User</a>
 * @apiParam {Object} [project] `QueryParam` ProjectUser's relation project. All params in <a href="#api-Project-GetProjectList" >@Project</a>
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine ProjectUserRelationsExample
 * @apiParamExample {json} Request With Relations Example
 *  {
 *      "with":                 "project, user, project.tasks",
 *      "project.id":           [">", 1],
 *      "project.tasks.active": 1,
 *      "user.full_name":       ["like", "%lorem%"]
 *  }
 *
 * @apiVersion 0.1.0
 */
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
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine WrongDateTimeFormatStartEndAt
 *
 * @apiError (Error 401) {String} Error Error
 *
 * @apiErrorExample {json} DateTime validation fail
 * {
 *   "error": "validation fail",
 *     "reason": {
 *     "start_at": [
 *       "The start at does not match the format Y-m-d\\TH:i:sP."
 *     ],
 *     "end_at": [
 *       "The end at does not match the format Y-m-d\\TH:i:sP."
 *     ]
 *   }
 * }
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine Relations
 * @apiParam {Object} [task]        `QueryParam` TimeInterval's relation task. All params in <a href="#api-Task-GetTaskList" >@Task</a>
 * @apiParam {Object} [user]        `QueryParam` TimeInterval's relation user. All params in <a href="#api-User-GetUserList" >@User</a>
 * @apiParam {Object} [screenshots] `QueryParam` TimeInterval's relation screenshots. All params in <a href="#api-Screenshot-GetScreenshotList" >@Screenshot</a>
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine RelationsExample
 * @apiParamExample {json} Request With Relations Example
 *  {
 *      "with":           "task,user,screenshots"
 *      "task.id":        [">", 1],
 *      "task.active":    1,
 *      "user.id":        [">", 1],
 *      "user.full_name": ["like", "%lorem%"],
 *      "screenshots.id": [">", 1]
 *  }
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine TaskRelations
 *
 * @apiParam {String} [with]                       For add relation model in response
 * @apiParam {Object} [timeIntervals] `QueryParam` Task's relation Time Intervals. All params in <a href="#api-Time_Interval-GetTimeIntervalList" >@Time_Intervals</a>
 * @apiParam {Object} [user]          `QueryParam` Task's relation user. All params in <a href="#api-User-GetUserList" >@User</a>
 * @apiParam {Object} [assigned]      `QueryParam` Task's relation user. All params in <a href="#api-User-GetUserList" >@User</a>
 * @apiParam {Object} [project]       `QueryParam` Task's relation user. All params in <a href="#api-Project-GetProjectList" >@Project</a>
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine TaskRelationsExample
 * @apiParamExample {json} Request With Relations Example
 *  {
 *      "with":                "project,user,timeIntervals,assigned"
 *      "user.id":             [">", 1],
 *      "project.task.active": 1,
 *      "assigned.full_name":  ["like", "%lorem%"]
 *  }
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine WrongDateTimeFormatStartEndAt
 *
 * @apiError (Error 401) {String} Error Error
 *
 * @apiErrorExample {json} DateTime validation fail
 * {
 *   "error": "validation fail",
 *     "reason": {
 *     "start_at": [
 *       "The start at does not match the format Y-m-d\\TH:i:sP."
 *     ],
 *     "end_at": [
 *       "The end at does not match the format Y-m-d\\TH:i:sP."
 *     ]
 *   }
 * }
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine ScreenshotRelations
 *
 * @apiParam {String} [with]                      For add relation model in response
 * @apiParam {Object} [timeInterval] `QueryParam` Screenshot's relation timeInterval. All params in <a href="#api-Time_Interval-GetTimeIntervalList" >@Time_Interval</a>
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine ScreenshotRelationsExample
 * @apiParamExample {json} Request-With-Relations-Example:
 *  {
 *      "with":                  "timeInterval,timeInterval.task",
 *      "timeInterval.tasks.id": [">", 1]
 *  }
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine RuleRelations
 *
 * @apiParam {String} [with]              For add relation model in response
 * @apiParam {Object} [role] `QueryParam` Rules's relation role. All params in <a href="#api-Roles-GetRolesList" >@Role</a>
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine RuleRelationsExample
 * @apiParamExample {json} Request-With-Relations-Example:
 *  {
 *      "with":      "role",
 *      "role.name": ["like", "%lorem%"]
 *  }
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine RolesRelations
 *
 * @apiParam {String} [with]               For add relation model in response
 * @apiParam {Object} [users] `QueryParam` Roles's relation users. All params in <a href="#api-User-GetUserList" >@User</a>
 * @apiParam {Object} [rules] `QueryParam` Roles's relation rules. All params in <a href="#api-Rule-GetRulesActions" >@Rules</a>
 *
 * @apiVersion 0.1.0
 */
/**
 * @apiDefine RolesRelationsExample
 * @apiParamExample {json} Request With Relations Example
 *  {
 *      "with":               "users,rules,users.tasks",
 *      "users.tasks.id":     [">", 1],
 *      "users.tasks.active": 1,
 *      "users.full_name":    ["like", "%lorem%"]
 *  }
 *
 * @apiVersion 0.1.0
 */
