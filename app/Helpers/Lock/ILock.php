<?php


namespace App\Helpers\Lock;


interface ILock
{
    public function setLock(bool $lock): void;

    public function lock(): void;

    public function unlock(): void;

    public function isLocked(): bool;
}