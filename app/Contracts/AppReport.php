<?php

namespace App\Contracts;

abstract class AppReport
{
    final public static function init(...$arguments): self
    {
        return new static(...$arguments);
    }

    abstract public function getReportId(): string;

    abstract public function getLocalizedReportName(): string;

    abstract public function store(string $filePath = null, string $disk = null, string $writerType = null, $diskOptions = []);
}
