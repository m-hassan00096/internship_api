<?php

namespace App\Enums;

enum ReviewStatus: int
{
    case PENDING = 0;
    case APPROVED = 1;
    case REJECTED = 2;
    case HIDDEN = 3;

    public function label(): string
    {
        return __('vendor/review.status.'.$this->name);
    }
}
