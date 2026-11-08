<?php

namespace App\Models;

use App\Enums\PermissionScope;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'scope',
    ];

    protected function casts(): array
    {
        return [
            'scope' => PermissionScope::class,
        ];
    }

    public function scopeForAdmin(Builder $query): Builder
    {
        return $query->where('scope', PermissionScope::ADMIN);
    }

    public function scopeForVendor(Builder $query): Builder
    {
        return $query->where('scope', PermissionScope::VENDOR);
    }
}
