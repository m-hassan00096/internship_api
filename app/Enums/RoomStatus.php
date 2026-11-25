<?php

namespace App\Enums;

enum RoomStatus: int
{
    case DRAFT = 0;
    case PUBLISHED = 1;
    case ARCHIVED = 2;

    public function label(): string
    {
        return __('vendor/room.status.'.$this->name);
    }
}
