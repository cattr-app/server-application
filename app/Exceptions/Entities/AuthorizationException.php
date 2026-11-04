<?php

namespace App\Exceptions\Entities;

use Flugg\Responder\Exceptions\Http\HttpException;

class AuthorizationException extends HttpException
{
    public const ERROR_TYPE_UNAUTHORIZED = 'authorization.unauthorized';

    public const ERROR_TYPE_CAPTCHA = 'authorization.captcha';

    public const ERROR_TYPE_BANNED = 'authorization.banned';

    public const ERROR_TYPE_TOKEN_MISMATCH = 'authorization.token_mismatch';

    public const ERROR_TYPE_TOKEN_EXPIRED = 'authorization.token_expired';

    public const ERROR_TYPE_USER_DISABLED = 'authorization.user_disabled';

    public const ERROR_TYPE_VALIDATION_FAILED = 'authorization.wrong_params';

    public const ERROR_TYPE_USER_NOT_FOUND = 'authorization.user_not_found';

    public const ERROR_TYPE_INVALID_PASSWORD_RESET_DATA = 'authorization.invalid_password_data';

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
