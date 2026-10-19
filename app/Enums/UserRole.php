<?php

namespace App\Enums;

enum UserRole: int
{
    case CLIENT = 1;
    case VENDOR = 2;
    case ADMIN = 3;
}
