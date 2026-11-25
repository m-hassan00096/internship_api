<?php

namespace App\Enums;

enum ContractStatus: int
{
    case DRAFT = 0;
    case ACTIVE = 1;
    case EXPIRED = 2;
    case TERMINATED = 3;

    public function label(): string
    {
        return __('vendor/showroom.contract_status.'.$this->name);
    }
}
