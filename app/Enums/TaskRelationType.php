<?php

namespace App\Enums;

enum TaskRelationType: string
{
    case FOLLOWS = 'follows';
    case PRECEDES = 'precedes';
}
