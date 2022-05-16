<?php

namespace App\Helpers;

use App\Exceptions\Entities\AuthorizationException;
use Cache;
use GuzzleHttp\Client;
use Request;
use Throwable;

class Recaptcha
{
    private string $userEmail;

    private bool $solved = false;

    /**
     * Increment amount of login tries for ip and login
     */
    public function incrementCaptchaAmounts(): void
    {
        if (!$this->captchaEnabled()) {
            return;
        }

        $cacheKey = $this->getCaptchaCacheKey();

        if (!Cache::store('octane')->has($cacheKey)) {
            Cache::store('octane')->put($cacheKey, 1, config('recaptcha.ttl'));
        } else {
            Cache::store('octane')->increment($cacheKey);
        }
    }

    /**
     * Shows captcha status from config
     *
     * @return bool
     */
    private function captchaEnabled(): bool
    {
        return config('recaptcha.enabled');
    }

    /**
     * Returns unique for ip and user login key
     * @return string
     */
    private function getCaptchaCacheKey(): string
    {
        $ip = Request::ip();
        $email = $this->userEmail;
        return "AUTH_RECAPTCHA_LIMITER_{$ip}_{$email}_ATTEMPTS";
    }

    /**
     * Forget about tries of user to login
     */
    public function clearCaptchaAmounts(): void
    {
        if (!$this->captchaEnabled()) {
            return;
        }

        Cache::store('octane')->forget($this->getCaptchaCacheKey());
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

        $this->userEmail = $credentials['email'];

        if (isset($credentials['recaptcha'])) {
            $this->solve($credentials['recaptcha']);
        }

        if ($this->needsCaptcha()) {
            $this->incrementBanAmounts();
            throw new AuthorizationException(AuthorizationException::ERROR_TYPE_CAPTCHA);
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

        $banData = Cache::store('octane')->get($cacheKey, null);

        if ($banData === null || !isset($banData['amounts'], $banData['time'])) {
            return false;
        }

        if ($banData['amounts'] < config('recaptcha.ban_attempts')) {
            return false;
        }

        if ($banData['time'] + config('recaptcha.rate_limiter_ttl') < time()) {
            Cache::store('octane')->forget($cacheKey);
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
        return $this->captchaEnabled() && config('recaptcha.rate_limiter_enabled');
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

        $banData = Cache::store('octane')->get($cacheKey);

        if ($banData === null || !isset($banData['amounts'])) {
            $banData = ['amounts' => 1, 'time' => time()];
        } else {
            $banData['amounts']++;
        }

        Cache::store('octane')->put($cacheKey, $banData, config('recaptcha.rate_limiter_ttl'));
    }

    /**
     * Sends request to google with captcha token for verify
     *
     * @param string $captchaToken
     */
    private function solve(string $captchaToken = ''): void
    {
        if (!$this->captchaEnabled()) {
            return;
        }

        if ($this->solved) {
            return;
        }

        $response = (new Client())->post(config('recaptcha.google_url'), [
            'form_params' => [
                'secret' => config('recaptcha.secret_key'),
                'response' => $captchaToken,
            ],
        ]);

        if ($response->getStatusCode() !== 200) {
            return;
        }

        $response = $response->getBody();

        if ($response === null) {
            return;
        }

        try {
            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR | JSON_THROW_ON_ERROR);
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

        $attempts = Cache::store('octane')->get($cacheKey, null);

        if ($attempts === null) {
            return false;
        }

        if ($attempts <= config('recaptcha.failed_attempts')) {
            return false;
        }

        return true;
    }
}
