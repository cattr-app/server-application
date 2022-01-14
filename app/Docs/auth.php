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
 * @apiDeprecated  since 5.0.0
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
