<?php

namespace App\Helpers\Lock;

class Lock implements ILock
{
    protected $locked = false;

    public function setLock(bool $lock): void
    {
        $this->locked = $lock;
    }

    public function lock(): void
    {
        $this->setLock(true);
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