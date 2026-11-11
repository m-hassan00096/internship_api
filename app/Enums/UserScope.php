<?php

namespace App\Enums;

enum UserScope: int
{
    case CLIENT = 0;
    case VENDOR = 1;
    case ADMIN = 2;
}
