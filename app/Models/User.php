<?php

namespace App\Models;

use App\Enums\UserActive;
use App\Enums\UserScope;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'avatar',
        'phone',
        'email',
        'role',
        'manager_id',
        'password',
        'user_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'role' => UserScope::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'user_active' => UserActive::class,
        ];
    }

    /**
     * قصر الاستعلام على الحسابات النشطة فقط
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('user_active', UserActive::ACTIVE);
    }

    public function isActive(): bool
    {
        return $this->user_active === UserActive::ACTIVE;
    }

    /**
     * الموظفون التابعون لهذا الحساب
     */
    public function employees(): HasMany
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /**
     * المدير أو التاجر التابع له هذا الموظف
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
