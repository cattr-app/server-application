<?php

namespace App\Exceptions\Entities;

use Throwable;

use Illuminate\Auth\Access\AuthorizationException as BaseAuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

use App\Exceptions\Interfaces\InfoExtendedException;
use App\Exceptions\Interfaces\TypedException;


/**
 * Class AuthorizationException
 * @package App\Exceptions\Entities
 */
class AuthorizationException extends BaseAuthorizationException
    implements TypedException, InfoExtendedException, HttpExceptionInterface
{
    public const ERROR_TYPE_UNAUTHORIZED = 'authorization.unauthorized';
    public const ERROR_TYPE_CAPTCHA = 'authorization.captcha';
    public const ERROR_TYPE_BANNED = 'authorization.banned_enhance_your_calm';
    public const ERROR_TYPE_TOKEN_MISMATCH = 'authorization.token_mismatch';
    public const ERROR_TYPE_TOKEN_EXPIRED = 'authorization.token_expired';
    public const ERROR_TYPE_USER_DISABLED = 'authorization.user_disabled';

    /**
     * @apiDefine 400Error
     * @apiError (Error 4xx) {String}   message  Message from server
     * @apiError (Error 4xx) {Boolean}  success  Indicates erroneous response when FALSE
     */

    /**
     * @apiDefine UnauthorizedError
     * @apiErrorExample {json} Unauthorized
     *  HTTP/1.1 401 Unauthorized
     *  {
     *    "success": false,
     *    "message": "Not authorized"
     *  }
     */

    /**
     * @apiDefine CaptchaError
     * @apiErrorExample {json} Captcha
     *  HTTP/1.1 429 Too Many Requests
     *  {
     *    "success": false,
     *    "message": "Invalid captcha"
     *  }
     */

    /**
     * @apiDefine LimiterError
     * @apiErrorExample {json} Limiter
     *  HTTP/1.1 423 Locked
     *  {
     *    "success": false,
     *    "message": "Enhance Your Calm"
     *  }
     */

    /**
     * @apiDefine TokenMismatchError
     * @apiErrorExample {json} Token mismatch
     *  HTTP/1.1 401 Unauthorized
     *  {
     *    "success": false,
     *    "message": "Token mismatch"
     *  }
     */

    /**
     * @apiDefine TokenExpiredError
     * @apiErrorExample {json} Token expired
     *  HTTP/1.1 401 Unauthorized
     *  {
     *    "success": false,
     *    "message": "Token expired"
     *  }
     */

    /**
     * @apiDefine UserDeactivatedError
     * @apiErrorExample {json} User deactivated
     *  HTTP/1.1 403 Forbidden
     *  {
     *    "success": false,
     *    "message": "User deactivated"
     *  }
     */

    /**
     * @apiDefine ParamsValidationError
     * @apiErrorExample {json} Params validation
     *  HTTP/1.1 400 Bad Request
     *  {
     *    "success": false,
     *    "message": "Ivalid params"
     *  }
     */

    /**
     * @apiDefine NoSuchUserError
     * @apiErrorExample {json} No such user
     *  HTTP/1.1 404 Not Found
     *  {
     *    "success": false,
     *    "message": "User with such email isn’t found"
     *  }
     */

    /**
     * @apiDefine InvalidPasswordResetDataError
     * @apiErrorExample {json} Invalid password reset data
     *  HTTP/1.1 401 Unauthorized
     *  {
     *    "success": false,
     *    "message": "Invalid password reset data"
     *  }
     */
    protected const ERRORS =
        [
            self::ERROR_TYPE_UNAUTHORIZED => ['code' => 401, 'message' => 'Not authorized'],
            self::ERROR_TYPE_CAPTCHA => ['code' => 429, 'message' => 'Invalid captcha',],
            self::ERROR_TYPE_BANNED => ['code' => 423, 'message' => 'Enhance Your Calm'],
            self::ERROR_TYPE_TOKEN_MISMATCH => ['code' => 401, 'message' => 'Token mismatch'],
            self::ERROR_TYPE_TOKEN_EXPIRED => ['code' => 401, 'message' => 'Token expired'],
            self::ERROR_TYPE_USER_DISABLED => ['code' => 403, 'message' => 'User deactivated'],
            self::ERROR_TYPE_VALIDATION_FAILED => ['code' => 400, 'message' => 'Ivalid params'],
            self::ERROR_TYPE_USER_NOT_FOUND => ['code' => 404, 'message' => 'User with such email isn’t found'],
            self::ERROR_TYPE_INVALID_PASSWORD_RESET_DATA => ['code' => 401, 'message' => 'Invalid password reset data'],
        ];

    /**
     * @var string
     */
    protected $type;

    /**
     * @var mixed
     */
    protected $info;

    /**
     * AuthorizationException constructor.
     * @param string $type
     * @param array|null $info
     * @param Throwable|null $previous
     */
    public function __construct($type = self::ERROR_TYPE_UNAUTHORIZED, $info = null, Throwable $previous = null)
    {
        $this->type = $type;
        $this->info = $info;

        parent::__construct($this->getMessageByType(), $this->getStatusCode(), $previous);
    }

    /**
     * @return mixed
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @return string
     */
    public function getMessageByType(): string
    {
        return self::ERRORS[$this->type]['message'];
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return self::ERRORS[$this->type]['code'];
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return [];
    }
}
