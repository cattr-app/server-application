<?php

namespace App\Enums;

enum ActivityType: string
{
    case ALL = 'all';
    case COMMENTS = 'comments';
    case HISTORY = 'history';
}
