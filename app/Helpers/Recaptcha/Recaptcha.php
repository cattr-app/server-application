<?php

namespace App\Helpers\Recaptcha;

class Recaptcha
{
    /**
     * @var RateLimiter
     */
    protected $rateLimiter;

    public function __construct(RateLimiter $rateLimiter)
    {
        $this->rateLimiter = $rateLimiter;
    }

    public static function isEnabled(): bool
    {
        return config('app.recaptcha.enabled', false);
    }

    public function getRateLimiter(): RateLimiter
    {
        return $this->rateLimiter;
    }

    protected function hasIp(string $login, string $ip): bool
    {
        if (!static::isEnabled()) {
            return false;
        }
        return \Cache::has('AUTH_RECAPTCHA_LIMITER_' . $ip . strtolower($login));
    }

    protected function getCacheIpData(string $login, string $ip): ?CacheIpData
    {
        if (!$this->hasIp($login, $ip)) {
            return null;
        }
        $data = \Cache::get('AUTH_RECAPTCHA_LIMITER_' . $ip . strtolower($login));
        if (!is_array($data)) {
            return null;
        }
        return CacheIpData::fromArray($data);
    }

    protected function allowedWithoutCaptcha(string $login, string $ip): bool
    {
        $data = $this->getCacheIpData($login, $ip);
        if (!$data) {
            return true;
        }
        if ((time() - $data->getTimestamp()) > env('RECAPTCHA_TTL')) {
            \Cache::forget('AUTH_RECAPTCHA_LIMITER_' . $ip . strtolower($login));
            return true;
        }

        return $data->getCount() < env('RECAPTCHA_FAILED_ATTEMPTS');
    }

    public function allowedWithoutCaptchaCurrentIp(string $login): bool
    {
        $ip = IpResolver::resolve();
        if (!$ip) {
            return true;
        }
        return $this->allowedWithoutCaptcha($login, $ip);
    }

    public function inc(string $login): void
    {
        $ip = IpResolver::resolve();
        if (!$ip) {
            return;
        }

        $cacheIpData = $this->getCacheIpData($login, $ip);
        if (!$cacheIpData) {
            $cacheIpData = new CacheIpData($ip);
        } else {
            if ((time() - $cacheIpData->getTimestamp()) > env('RECAPTCHA_TTL')) {
                $cacheIpData->reset();
            } else {
                $cacheIpData->touch();
            }
        }

        if ($cacheIpData->getCount() > env('RECAPTCHA_FAILED_ATTEMPTS')) {
            $this->rateLimiter->inc($ip);
        }

        \Cache::forever('AUTH_RECAPTCHA_LIMITER_' . $ip . strtolower($login), $cacheIpData->toArray());
    }

    public function forget(string $login, string $ip): void
    {
        if ($this->hasIp($login, $ip)) {
            \Cache::forget('AUTH_RECAPTCHA_LIMITER_' . $ip . strtolower($login));
            $this->rateLimiter->forget($ip);
        }
    }

    public function forgetCurrentIp(string $login): void
    {
        $ip = IpResolver::resolve();
        if ($ip) {
            $this->forget($login, $ip);
        }
    }

    public function testCaptcha(string $token): bool
    {
        $response = (new \GuzzleHttp\Client())->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' => env('RECAPTCHA_SECRET_KEY'),
                'response' => $token,
            ],
        ]);
        if ($response->getStatusCode() != 200) {
            return false;
        }
        $response = $response->getBody();
        if (empty($response)) {
            return false;
        }
        try {
            $data = json_decode($response, true);
        } catch (\Throwable $throwable) {
            return false;
        }
        return is_array($data) && isset($data['success']) && $data['success'] === true;
    }
}
