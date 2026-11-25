<?php

namespace App\Enums;

enum DiscountType: int
{
    case PERCENTAGE = 1;
    case FIXED = 2;

    public function label(): string
    {
        return __('vendor/room.discount_type.'.$this->name);
    }
}
