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


    protected const ERRORS =
        [
            self::ERROR_TYPE_UNAUTHORIZED => ['code' => 401, 'message' => 'Not authorized'],
            self::ERROR_TYPE_CAPTCHA => ['code' => 429, 'message' => 'Invalid captcha',],
            self::ERROR_TYPE_BANNED => ['code' => 420, 'message' => 'Enhance Your Calm'],
            self::ERROR_TYPE_TOKEN_MISMATCH => ['code' => 401, 'message' => 'Token mismatch'],
            self::ERROR_TYPE_TOKEN_EXPIRED => ['code' => 401, 'message' => 'Token expired'],
            self::ERROR_TYPE_USER_DISABLED => ['code' => 403, 'message' => 'User deactivated']
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
