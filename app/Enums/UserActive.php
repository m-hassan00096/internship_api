<?php

namespace App\Enums;

enum UserActive: int
{
    case INACTIVE = 0;
    case ACTIVE = 1;
    case PENDING_PASSWORD = 2;

    public function label(): string
    {
        return __('auth.user_active.labels.'.$this->name);
    }

    public function message(): string
    {
        return __('auth.user_active.messages.'.$this->name);
    }

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public static function toString(int $status): string
    {
        return self::tryFrom($status)?->label()
            ?? __('auth.user_active.labels.UNKNOWN');
    }
}
