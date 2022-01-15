<?php

namespace App\Exceptions\Entities;

use Flugg\Responder\Exceptions\Http\HttpException;

class AuthorizationException extends HttpException
{
    /**
     * @apiDefine 400Error
     * @apiError (Error 4xx) {String}   message     Message from server
     * @apiError (Error 4xx) {Boolean}  success     Indicates erroneous response when `FALSE`
     * @apiError (Error 4xx) {String}   error_type  Error type
     *
     * @apiVersion 1.0.0
     */

    /**
     * @apiDefine UnauthorizedError
     * @apiErrorExample {json} Unauthorized
     *  HTTP/1.1 401 Unauthorized
     *  {
     *    "message": "Not authorized",
     *    "error_type": "authorization.unauthorized"
     *  }
     *
     * @apiVersion 1.0.0
     */
    public const ERROR_TYPE_UNAUTHORIZED = 'authorization.unauthorized';

    /**
     * @apiDefine CaptchaError
     * @apiError (Error 429) {Object}  info           Additional info from server
     * @apiError (Error 429) {String}  info.site_key  Public site key for rendering reCaptcha
     *
     * @apiErrorExample {json} Captcha
     *  HTTP/1.1 429 Too Many Requests
     *  {
     *    "message": "Invalid captcha",
     *    "error_type": "authorization.captcha"
     *  }
     *
     * @apiVersion 1.0.0
     */
    public const ERROR_TYPE_CAPTCHA = 'authorization.captcha';

    /**
     * @apiDefine LimiterError
     * @apiErrorExample {json} Limiter
     *  HTTP/1.1 423 Locked
     *  {
     *    "message": "Enhance Your Calm",
     *    "error_type": "authorization.banned_enhance_your_calm"
     *  }
     *
     * @apiVersion 1.0.0
     */
    public const ERROR_TYPE_BANNED = 'authorization.banned';

    /**
     * @apiDefine TokenMismatchError
     * @apiErrorExample {json} Token mismatch
     *  HTTP/1.1 401 Unauthorized
     *  {
     *    "message": "Token mismatch",
     *    "error_type": "authorization.token_mismatch"
     *  }
     *
     * @apiVersion 1.0.0
     */
    public const ERROR_TYPE_TOKEN_MISMATCH = 'authorization.token_mismatch';

    /**
     * @apiDefine TokenExpiredError
     * @apiErrorExample {json} Token expired
     *  HTTP/1.1 401 Unauthorized
     *  {
     *    "message": "Token expired",
     *    "error_type": "authorization.token_expired"
     *  }
     *
     * @apiVersion 1.0.0
     */
    public const ERROR_TYPE_TOKEN_EXPIRED = 'authorization.token_expired';

    /**
     * @apiDefine UserDeactivatedError
     * @apiErrorExample {json} User deactivated
     *  HTTP/1.1 403 Forbidden
     *  {
     *    "message": "User deactivated",
     *    "error_type": "authorization.user_disabled"
     *  }
     *
     * @apiVersion 1.0.0
     */
    public const ERROR_TYPE_USER_DISABLED = 'authorization.user_disabled';

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
    public const ERROR_TYPE_VALIDATION_FAILED = 'authorization.wrong_params';

    /**
     * @apiDefine NoSuchUserError
     * @apiErrorExample {json} No such user
     *  HTTP/1.1 404 Not Found
     *  {
     *    "message": "User with such email isn’t found",
     *    "error_type": "authorization.user_not_found"
     *  }
     *
     * @apiVersion 1.0.0
     */
    public const ERROR_TYPE_USER_NOT_FOUND = 'authorization.user_not_found';

    /**
     * @apiDefine InvalidPasswordResetDataError
     * @apiErrorExample {json} Invalid password reset data
     *  HTTP/1.1 401 Unauthorized
     *  {
     *    "message": "Invalid password reset data",
     *    "error_type": "authorization.invalid_password_data"
     *  }
     *
     * @apiVersion 1.0.0
     */
    public const ERROR_TYPE_INVALID_PASSWORD_RESET_DATA = 'authorization.invalid_password_data';

    /**
     * @apiDefine ForbiddenError
     * @apiErrorExample {json} Forbidden
     *  HTTP/1.1 403 Forbidden
     *  {
     *    "message": "Access denied to this item",
     *    "error_type": "authorization.forbidden"
     *  }
     *
     * @apiVersion 1.0.0
     */
    public const ERROR_TYPE_FORBIDDEN = 'authorization.forbidden';

    protected const ERRORS = [
        self::ERROR_TYPE_UNAUTHORIZED => ['code' => 401, 'message' => 'Not authorized'],
        self::ERROR_TYPE_CAPTCHA => ['code' => 429, 'message' => 'Invalid captcha',],
        self::ERROR_TYPE_BANNED => ['code' => 423, 'message' => 'Enhance Your Calm'],
        self::ERROR_TYPE_TOKEN_MISMATCH => ['code' => 401, 'message' => 'Token mismatch'],
        self::ERROR_TYPE_TOKEN_EXPIRED => ['code' => 401, 'message' => 'Token expired'],
        self::ERROR_TYPE_USER_DISABLED => ['code' => 403, 'message' => 'User deactivated'],
        self::ERROR_TYPE_VALIDATION_FAILED => ['code' => 400, 'message' => 'Invalid params'],
        self::ERROR_TYPE_USER_NOT_FOUND => ['code' => 404, 'message' => 'User with such email isn’t found'],
        self::ERROR_TYPE_INVALID_PASSWORD_RESET_DATA => ['code' => 401, 'message' => 'Invalid password reset data'],
        self::ERROR_TYPE_FORBIDDEN => ['code' => 403, 'message' => 'This action is unauthorized']
    ];

    public function __construct($type = self::ERROR_TYPE_UNAUTHORIZED)
    {
        $this->errorCode = $type;
        $this->status = self::ERRORS[$type]['code'];

        parent::__construct(self::ERRORS[$type]['message']);
    }
}
