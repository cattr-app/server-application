<?php
/**
 * @apiDeprecated  since 1.0.0 use now (#Password_Reset:Process)
 * @api            {post} /api/auth/reset Reset
 * @apiDescription Get user JWT
 *
 *
 * @apiVersion     1.0.0
 * @apiName        Reset
 * @apiGroup       Auth
 */

/**
 * @apiDeprecated  since 1.0.0 use now (#Password_Reset:Request)
 * @api            {post} /api/auth/send-reset Send reset e-mail
 * @apiDescription Get user JWT
 *
 *
 * @apiVersion     1.0.0
 * @apiName        Send reset
 * @apiGroup       Auth
 */

/**
 * @apiDeprecated  since 4.0.0
 * @api            {post} /auth/refresh Refresh
 * @apiDescription Refreshes JWT
 *
 * @apiVersion     1.0.0
 * @apiName        Refresh
 * @apiGroup       Auth
 *
 * @apiUse         AuthHeader
 *
 * @apiSuccess {String}   access_token  Token
 * @apiSuccess {String}   token_type    Token Type
 * @apiSuccess {String}   expires_in    Token TTL 8601String Date
 *
 * @apiUse         400Error
 * @apiUse         UnauthorizedError
 */

/**
 * @apiDeprecated since 4.0.0
 * @apiDefine ParamsValidationError
 * @apiErrorExample {json} Params validation
 *  HTTP/1.1 400 Bad Request
 *  {
 *    "message": "Invalid params",
 *    "error_type": "authorization.wrong_params"
 *  }
 *
 * @apiVersion 1.0.0
 */

/**
 * @api            {post} /auth/logout Logout
 * @apiDescription Invalidate JWT
 *
 * @apiVersion     1.0.0
 * @apiName        Logout
 * @apiGroup       Auth
 *
 * @apiUse         AuthHeader
 *
 * @apiSuccess {String}   message  Message from server
 *
 * @apiSuccessExample {json} Response Example
 *  HTTP/1.1 200 OK
 *  {
 *    "message": "Successfully logged out"
 *  }
 *
 * @apiUse         400Error
 * @apiUse         UnauthorizedError
 */

/**
 * @api            {post} /auth/logout-from-all Logout from all
 * @apiDescription Invalidate all user JWT
 *
 * @apiVersion     1.0.0
 * @apiName        Logout all
 * @apiGroup       Auth
 *
 * @apiUse         AuthHeader
 *
 * @apiSuccess {String}   message  Message from server
 *
 * @apiSuccessExample {json} Response Example
 *  HTTP/1.1 200 OK
 *  {
 *    "message": "Successfully logged out from all sessions"
 *  }
 *
 * @apiUse         400Error
 * @apiUse         UnauthorizedError
 */

/**
 * @api            {get} /auth/me Me
 * @apiDescription Get authenticated User Entity
 *
 * @apiVersion     1.0.0
 * @apiName        Me
 * @apiGroup       Auth
 *
 * @apiUse         AuthHeader
 *
 * @apiSuccess {Object}   user     User Entity
 *
 * @apiUse         UserObject
 *
 * @apiSuccessExample {json} Response Example
 *  HTTP/1.1 200 OK
 *  {
 *    "user": {
 *      "id": 1,
 *      "full_name": "Admin",
 *      "email": "admin@example.com",
 *      "url": "",
 *      "company_id": 1,
 *      "avatar": "",
 *      "screenshots_active": 1,
 *      "manual_time": 0,
 *      "computer_time_popup": 300,
 *      "blur_screenshots": 0,
 *      "web_and_app_monitoring": 1,
 *      "screenshots_interval": 9,
 *      "active": "active",
 *      "deleted_at": null,
 *      "created_at": "2018-09-25 06:15:08",
 *      "updated_at": "2018-09-25 06:15:08",
 *      "timezone": null
 *    }
 *  }
 *
 * @apiUse         400Error
 * @apiUse         UnauthorizedError
 */
/**
 * @api            {get} /auth/desktop-key Issue key
 * @apiDescription Issues key for desktop auth
 *
 * @apiVersion     1.0.0
 * @apiName        Issue key
 * @apiGroup       Auth
 *
 * @apiUse         AuthHeader
 *
 * @apiSuccess {String}   access_token  Token
 * @apiSuccess {String}   token_type    Token Type
 * @apiSuccess {String}   expires_in    Token TTL 8601String Date
 *
 * @apiSuccessExample {json} Response Example
 *  HTTP/1.1 200 OK
 *  {
 *    "access_token": "r6nPiGocAWdD5ZF60dTkUboVAWSXsUScpp7m9x9R",
 *    "token_type": "desktop",
 *    "expires_in": "2020-12-26T14:18:32+00:00"
 *  }
 *
 * @apiUse         UnauthorizedError
 */
/**
 * @api            {post} /auth/login Login
 * @apiDescription Get user JWT
 *
 * @apiVersion     1.0.0
 * @apiName        Login
 * @apiGroup       Auth
 *
 * @apiParam {String}  email        User email
 * @apiParam {String}  password     User password
 * @apiParam {String}  [recaptcha]  Recaptcha token
 *
 * @apiParamExample {json} Request Example
 *  {
 *    "email": "johndoe@example.com",
 *    "password": "amazingpassword",
 *    "recaptcha": "03AOLTBLR5UtIoenazYWjaZ4AFZiv1OWegWV..."
 *  }
 *
 * @apiSuccess {String}   access_token  Token
 * @apiSuccess {String}   token_type    Token Type
 * @apiSuccess {ISO8601}  expires_in    Token TTL
 * @apiSuccess {Object}   user          User Entity
 *
 * @apiUse         UserObject
 *
 * @apiSuccessExample {json} Response Example
 *  HTTP/1.1 200 OK
 *  {
 *    "access_token": "16184cf3b2510464a53c0e573c75740540fe...",
 *    "token_type": "bearer",
 *    "expires_in": "2020-12-26T14:18:32+00:00",
 *    "user": {}
 *  }
 *
 * @apiUse         400Error
 * @apiUse         ParamsValidationError
 * @apiUse         UnauthorizedError
 * @apiUse         UserDeactivatedError
 * @apiUse         CaptchaError
 * @apiUse         LimiterError
 */
