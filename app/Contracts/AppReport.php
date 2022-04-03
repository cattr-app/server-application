<?php

namespace App\Contracts;

interface AppReport
{
    public function getReportId(): string;

    public function getLocalizedReportName(): string;

    public function store(string $filePath = null, string $disk = null, string $writerType = null, $diskOptions = []);
}
