<?php

namespace App\Enums;

enum RegistrationStatus: int
{
    case NEW = 0;
    case UNDER_REVIEW = 1;
    case APPROVED = 2;
    case REJECTED = 3;

    public function label(): string
    {
        return __('vendor/registration.labels.'.$this->name);
    }

    public function message(): string
    {
        return __('vendor/registration.messages.'.$this->name);
    }

    public static function toString(int $status): string
    {
        return self::tryFrom($status)?->label()
            ?? __('vendor/registration.labels.UNKNOWN');
    }
}
