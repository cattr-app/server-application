<?php

namespace App\Exceptions\Entities;


class CaptchaException extends AuthorizationException
{
    public function __construct()
    {
        parent::__construct(static::ERROR_TYPE_CAPTCHA);
    }

    public static function getSiteKey()
    {
        return env('RECAPTCHA_ENABLED') ? env('RECAPTCHA_SITE_KEY') : '';
    }
}
