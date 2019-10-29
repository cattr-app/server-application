<?php

namespace App\Exceptions\Entities;

use Throwable;

class AuthorizationCaptchaException extends AuthorizationException
{
    public function __construct()
    {
        parent::__construct(static::ERROR_TYPE_CAPTCHA, null);
    }

    public static function getSiteKey()
    {
        return env('RECAPTCHA_ENABLED') ? env('RECAPTCHA_SITE_KEY') : '';
    }
}
