<?php

namespace App\Enums;

enum PermissionScope: int
{
    case ADMIN = 0;
    case VENDOR = 1;
}
