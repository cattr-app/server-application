<?php

namespace App\Helpers\Recaptcha;

class CacheIpData
{
    protected $ip;

    protected $count;

    protected $timestamp;

    public function __construct(?string $ip = null)
    {
        if ($ip) {
            $this->ip = $ip;
            $this->reset();
        }
    }

    public function touch(): void
    {
        $this->count += 1;
        $this->timestamp = time();
    }

    public function reset(): void
    {
        $this->count = 0;
        $this->touch();
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function toArray(): array
    {
        return [
            'ip' => $this->ip,
            'count' => $this->count,
            'timestamp' => $this->timestamp
        ];
    }

    public static function fromArray(array $data): CacheIpData
    {
        $rld = new CacheIpData();
        $rld->ip = $data['ip'];
        $rld->count = $data['count'];
        $rld->timestamp = $data['timestamp'];
        return $rld;
    }
}
