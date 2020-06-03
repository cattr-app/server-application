<?php

namespace App\Helpers\Lock;

class Lock implements LockInterface
{
    protected bool $locked = false;

    public function lock(): void
    {
        $this->setLock(true);
    }

    public function setLock(bool $lock): void
    {
        $this->locked = $lock;
    }

    public function unlock(): void
    {
        $this->setLock(false);
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }
}
