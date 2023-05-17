<?php

namespace App\Enums;

enum ScreenshotEnabledOptions: string
{
    case FORBIDDEN = 'forbidden';
    case REQUIRED = 'required';
    case OPTIONAL = 'optional';
}