<?php

namespace App\Enums;

enum PackageStatus: int
{
    case DRAFT = 0;
    case PUBLISHED = 1;
    case ARCHIVED = 2;

    public function label(): string
    {
        return __('vendor/package.status.'.$this->name);
    }
}
