<?php

namespace App\Enums;

enum Role: int
{
    case ANY = -1;

    case ADMIN = 0;
    case MANAGER = 1;
    case USER = 2;
    case AUDITOR = 3;
}
