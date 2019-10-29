<?php

namespace App\Exceptions\Entities;

use App\Exceptions\Interfaces\{
    ReasonableException, TypedException
};
use \Illuminate\Auth\Access\AuthorizationException as AuthorizationExceptionCore;
use Throwable;

/**
 * Class AuthorizationException
 * @package App\Exceptions\Entities
 */
class AuthorizationException extends AuthorizationExceptionCore implements TypedException, ReasonableException
{
    public const ERROR_TYPE_UNAUTHORIZED = 'authorization.unauthorized';
    public const ERROR_TYPE_CAPTCHA = 'authorization.captcha';
    public const ERROR_TYPE_BANNED = 'authorization.banned_enhance_your_calm';
    public const ERROR_TYPE_TOKEN_MISMATCH = 'authorization.token_mismatch';
    public const ERROR_TYPE_TOKEN_EXPIRED = 'authorization.token_expired';
    public const ERROR_TYPE_USER_DISABLED = 'authorization.user_disabled';

    /**
     * @var string
     */
    protected $type = '';

    /**
     * AuthorizationException constructor.
     * @param string $type
     * @param Throwable|null $previous
     */
    public function __construct($type = self::ERROR_TYPE_UNAUTHORIZED, Throwable $previous = null)
    {
        $this->type = $type;

        parent::__construct(
            __('Access denied'),
            static::codeByType($type),
            $previous
        );
    }

    /**
     * @param string $type
     * @return int
     */
    public static function codeByType(string $type): int
    {
        return [
                static::ERROR_TYPE_UNAUTHORIZED => 401,
                static::ERROR_TYPE_CAPTCHA => 429,
                static::ERROR_TYPE_BANNED => 420,
                static::ERROR_TYPE_TOKEN_MISMATCH => 401,
                static::ERROR_TYPE_TOKEN_EXPIRED => 401,
                static::ERROR_TYPE_USER_DISABLED => 403,
            ][$type] ?? 400;
    }

    /**
     * @return string
     */
    public function getReason(): string
    {
        return __([
                static::ERROR_TYPE_UNAUTHORIZED => 'Not authorized',
                static::ERROR_TYPE_CAPTCHA => 'Not authorized or captcha invalid',
                static::ERROR_TYPE_BANNED => 'Enhance Your Calm',
                static::ERROR_TYPE_TOKEN_MISMATCH => 'Token mismatch',
                static::ERROR_TYPE_TOKEN_EXPIRED => 'Token expired',
                static::ERROR_TYPE_USER_DISABLED => 'User deactivated',
            ][$this->type] ?? 'Unknown reason');
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }
}
