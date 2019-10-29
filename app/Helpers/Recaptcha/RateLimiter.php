<?php

namespace App\Helpers\Recaptcha;

class RateLimiter
{
    public static function isEnabled(): bool
    {
        return env('RECAPTCHA_ENABLED') && env('RATE_LIMITER_ENABLED');
    }

    protected function hasIp(string $ip): bool
    {
        if (!static::isEnabled()) {
            return false;
        }
        return \Cache::has('AUTH_RATE_LIMITER_' . $ip);
    }

    protected function getCacheIpData(string $ip): ?CacheIpData
    {
        if (!$this->hasIp($ip)) {
            return null;
        }
        $data = \Cache::get('AUTH_RATE_LIMITER_' . $ip);
        if (!is_array($data)) {
            return null;
        }
        return CacheIpData::fromArray($data);
    }

    public function allowed(string $ip): bool
    {
        $data = $this->getCacheIpData($ip);
        if (!$data) {
            return true;
        }
        if ((time() - $data->getTimestamp()) > env('RATE_LIMITER_TTL')) {
            \Cache::forget('AUTH_RATE_LIMITER_' . $ip);
            return true;
        }
        return $data->getCount() < env('RECAPTCHA_BAN_ATTEMPTS');
    }

    public function allowedIp(): bool
    {
        $ip = IpResolver::resolve();
        if (!$ip) {
            return true;
        }
        return $this->allowed($ip);
    }

    public function inc(string $ip): void
    {
        $cacheIpData = $this->getCacheIpData($ip);
        if (!$cacheIpData) {
            $cacheIpData = new CacheIpData($ip);
        } else {
            if ((time() - $cacheIpData->getTimestamp()) > env('RATE_LIMITER_TTL')) {
                $cacheIpData->reset();
            } else {
                $cacheIpData->touch();
            }
        }

        \Cache::forever('AUTH_RATE_LIMITER_' . $ip, $cacheIpData->toArray());
    }

    public function incIp(): void
    {
        $ip = IpResolver::resolve();
        if ($ip) {
            $this->inc($ip);
        }
    }

    public function forget(string $ip): void
    {
        if ($this->hasIp($ip)) {
            \Cache::forget('AUTH_RATE_LIMITER_' . $ip);
        }
    }

    public function forgetIp(): void
    {
        $ip = IpResolver::resolve();
        if ($ip) {
            $this->forget($ip);
        }
    }
}
