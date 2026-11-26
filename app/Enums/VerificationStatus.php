<?php

namespace App\Enums;

enum VerificationStatus: int
{
    case PENDING = 0;
    case APPROVED = 1;
    case REJECTED = 2;
    case EXPIRED = 3;
    case UNVERIFIED = 4;

    public function label(): string
    {
        return __('vendor/showroom.verification_status.'.$this->name);
    }
}
