<?php

namespace App\Enums;

enum PromotionStatus: int
{
    case PENDING = 0;
    case APPROVED = 1;
    case ACTIVE = 2;
    case EXPIRED = 3;
    case REJECTED = 4;

    public function label(): string
    {
        return __('vendor/promotion.status.'.$this->name);
    }
}
