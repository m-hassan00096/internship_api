<?php

namespace App\Enums;

enum RoomAvailabilityStatus: int
{
    case AVAILABLE = 1;
    case MADE_TO_ORDER = 2;
    case OUT_OF_STOCK = 3;

    public function label(): string
    {
        return __('vendor/room.availability.'.$this->name);
    }
}
