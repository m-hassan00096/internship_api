<?php

namespace App\Enums;

enum ShowroomStatus: int
{
    case DRAFT = 0;
    case ACTIVE = 1;
    case SUSPENDED = 2;
    case TERMINATED = 3;

    public function label(): string
    {
        return __('vendor/showroom.status.'.$this->name);
    }
}
