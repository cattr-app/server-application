<?php

namespace App\Helpers;

use App\Exceptions\Entities\AuthorizationException;
use Cache;
use GuzzleHttp\Client;
use Request;
use Throwable;

/**
 * Class RecaptchaHelper
 * @package App\Helpers
 */
class RecaptchaHelper
{
    private const DEFAULT_CONFIG = [
        'RECAPTCHA_ENABLED' => false,
        'RECAPTCHA_SITE_KEY' => '',
        'RECAPTCHA_SECRET_KEY' => '',
        'RECAPTCHA_GOOGLE_URL' => 'http://localhost',
        'RECAPTCHA_TTL' => 3600,
        'RECAPTCHA_FAILED_ATTEMPTS' => 10,
        'RECAPTCHA_BAN_ATTEMPTS' => 10,
        'RATE_LIMITER_ENABLED' => false,
        'RATE_LIMITER_TTL' => 3600
    ];
    /**
     * @var string $user
     */
    private $user;
    /**
     * @var bool $solved
     */
    private $solved = false;

    /**
     * Increment amount of login tries for ip and login
     */
    public function incrementCaptchaAmounts(): void
    {
        if (!$this->captchaEnabled()) {
            return;
        }

        $cacheKey = $this->getCaptchaCacheKey();

        if (!Cache::has($cacheKey)) {
            Cache::put($cacheKey, 1, env('RECAPTCHA_TTL', self::DEFAULT_CONFIG['RECAPTCHA_TTL']));
        } else {
            Cache::increment($cacheKey);
        }
    }

    /**
     * Shows captcha status from config
     *
     * @return bool
     */
    private function captchaEnabled(): bool
    {
        return env('RECAPTCHA_ENABLED', self::DEFAULT_CONFIG['RECAPTCHA_ENABLED']);
    }

    /**
     * Returns unique for ip and user login key
     * @return string
     */
    private function getCaptchaCacheKey(): string
    {
        $ip = Request::ip();
        $login = $this->user;
        return "AUTH_RECAPTCHA_LIMITER_{$ip}_{$login}_ATTEMPTS";
    }

    /**
     * Forget about tries of user to login
     */
    public function clearCaptchaAmounts(): void
    {
        if (!$this->captchaEnabled()) {
            return;
        }

        Cache::forget($this->getCaptchaCacheKey());
    }

    /**
     * @param array $credentials
     * @throws AuthorizationException
     */
    public function check(array $credentials): void
    {
        if ($this->isBanned()) {
            $this->incrementBanAmounts();
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_BANNED);
        }

        $this->user = $credentials['login'];

        if (isset($credentials['recaptcha'])) {
            $this->solve($credentials['recaptcha']);
        }

        if ($this->needsCaptcha()) {
            $this->incrementBanAmounts();
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_CAPTCHA,
                ['site_key' => env('RECAPTCHA_SITE_KEY', self::DEFAULT_CONFIG['RECAPTCHA_SITE_KEY'])]);
        }
    }

    /**
     * Tests if user on ban list
     *
     * @return bool
     */
    private function isBanned(): bool
    {
        if (!$this->banEnabled()) {
            return false;
        }

        $cacheKey = $this->getBanCacheKey();

        $banData = Cache::get($cacheKey, null);

        if (is_null($banData)) {
            return false;
        }

        if ($banData['amounts'] < env('RECAPTCHA_BAN_ATTEMPTS', self::DEFAULT_CONFIG['RECAPTCHA_BAN_ATTEMPTS'])) {
            return false;
        }

        if ($banData['time'] + env('RATE_LIMITER_TTL', self::DEFAULT_CONFIG['RATE_LIMITER_TTL']) < time()) {
            Cache::forget($cacheKey);
            return false;
        }

        return true;
    }

    /**
     * Shows ban limiter status from config
     *
     * @return bool
     */
    private function banEnabled(): bool
    {
        return $this->captchaEnabled() && env('RATE_LIMITER_ENABLED', self::DEFAULT_CONFIG['RATE_LIMITER_ENABLED']);
    }

    /**
     * Returns unique for ip key
     *
     * @return string
     */
    private function getBanCacheKey(): string
    {
        $ip = Request::ip();
        return "AUTH_RATE_LIMITER_{$ip}";
    }

    /**
     * Increment amount of tries for ip
     */
    private function incrementBanAmounts(): void
    {
        if (!$this->banEnabled()) {
            return;
        }

        $cacheKey = $this->getBanCacheKey();

        $banData = Cache::get($cacheKey);

        if (is_null($banData)) {
            $banData = ['amounts' => 1, 'time' => time()];
        } else {
            $banData['amounts']++;
        }

        Cache::put($cacheKey, $banData, env('RATE_LIMITER_TTL', self::DEFAULT_CONFIG['RATE_LIMITER_TTL']));
    }

    /**
     * Sends request to google with captcha token for verify
     *
     * @param string $captchaToken
     */
    private function solve(string $captchaToken = ""): void
    {
        if (!$this->captchaEnabled()) {
            return;
        }

        if ($this->solved) {
            return;
        }

        $response = (new Client())->post(env('RECAPTCHA_GOOGLE_URL', 'http://localhost'), [
            'form_params' => [
                'secret' => env('RECAPTCHA_SECRET_KEY', self::DEFAULT_CONFIG['RECAPTCHA_SECRET_KEY']),
                'response' => $captchaToken,
            ],
        ]);

        if ($response->getStatusCode() != 200) {
            return;
        }

        $response = $response->getBody();

        if (empty($response)) {
            return;
        }

        try {
            $data = json_decode($response, true);
        } catch (Throwable $throwable) {
            return;
        }

        if (isset($data['success']) && $data['success'] === true) {
            $this->solved = true;
        }
    }

    /**
     * Tests if we need to show captcha to user
     *
     * @return bool
     */
    private function needsCaptcha(): bool
    {
        if (!$this->captchaEnabled()) {
            return false;
        }

        if ($this->solved) {
            return false;
        }

        $cacheKey = $this->getCaptchaCacheKey();

        $attempts = Cache::get($cacheKey, null);

        if (is_null($attempts)) {
            return false;
        }

        if ($attempts <= env('RECAPTCHA_FAILED_ATTEMPTS', self::DEFAULT_CONFIG['RECAPTCHA_FAILED_ATTEMPTS'])) {
            return false;
        }

        return true;
    }
}
